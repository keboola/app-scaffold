<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\SyncActions;

use Keboola\Component\JsonHelper;
use Keboola\ScaffoldApp\Importer\Decorator\DecoratorInterface;
use Keboola\StorageApi\Client as StorageApiClient;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\Metadata;
use Keboola\StorageApi\Options\Components\ListComponentsOptions;
use Keboola\StorageApi\Options\SearchTablesOptions;
use Symfony\Component\Finder\Finder;

class ObjectLister
{
    private const NEW_TRANSFORMATION_COMPONENTS = ['keboola.python-transformation-v2',
        'keboola.snowflake-transformation', 'keboola.synapse-transformation', 'keboola.csas-python-transformation-v2'];
    private const LEGACY_TRANSFORMATION_COMPONENTS = ['transformation'];

    public static function listObjects(StorageApiClient $storageApiClient, Components $components): array
    {
        $listOptions = new ListComponentsOptions();
        $listOptions->setInclude(['configurations']);
        $components = $components->listComponents($listOptions) ?? [];
        $scaffoldObjects = [];
        foreach ($components as $component) {
            foreach ($component['configurations'] as $configuration) {
                if (preg_match('#\\|ScaffoldId: ([a-zA-Z]+)\\|#', $configuration['description'], $matches)) {
                    $scaffoldObjects[$matches[1]]['configurations'][] = [
                        'configurationId' => $configuration['id'],
                        'componentId' => $component['id'],
                    ];
                }
            }
        }
        $searchOptions = new SearchTablesOptions();
        $searchOptions->setMetadataKey(DecoratorInterface::SCAFFOLD_ID_TAG);
        $tables = $storageApiClient->searchTables($searchOptions) ?? [];
        $metadata = new Metadata($storageApiClient);
        foreach ($tables as $table) {
            $values = $metadata->listTableMetadata($table['id']);
            foreach ($values as $value) {
                if ($value['key'] === DecoratorInterface::SCAFFOLD_ID_TAG) {
                    $scaffoldObjects[$value['value']]['tables'][] = $table['id'];
                }
            }
        }
        return $scaffoldObjects;
    }

    public static function isScaffoldUsable(
        array $manifest,
        bool $hasLegacyTransformations,
        bool $hasNewTransformations,
        array $componentIds
    ): array {
        foreach ($manifest['inputs'] as $input) {
            if (!$hasLegacyTransformations &&
                (in_array($input['componentId'], self::LEGACY_TRANSFORMATION_COMPONENTS))
            ) {
                return false;
            }
            if (!$hasNewTransformations && (in_array($input['componentId'], self::NEW_TRANSFORMATION_COMPONENTS))) {
                return false;
            }
            if (!in_array($input['componentId'], $componentIds)) {
                return false;
            }
        }
        return true;
    }

    public static function listScaffolds(StorageApiClient $storageApiClient, string $scaffoldsDir): array
    {
        $tokenInfo = $storageApiClient->verifyToken();
        /**
         * If there is NOT new-transformations-only flag, then we ASSUME that legacy transformations are available.
         * If it is legacy stack (AWS EU + US), then we ASSUME that new transformations are NOT available
         *
         * Both new and legacy transformations are published in all stacks, their availability is driven by
         *  feature, but the feature is still incomplete.
         */
        $hasLegacyTransformations = !in_array('new-transformations-only', $tokenInfo['owner']['features']);
        $hasNewTransformations = !in_array(
            $storageApiClient->getApiUrl(),
            [
                'https://connection.keboola.com/',
                'https://connection.eu-central-1.keboola.com/',
            ]
        );
        $scaffolds = (new Finder())->in($scaffoldsDir)
            ->directories()->depth(0);

        $index = $storageApiClient->indexAction();
        $components = [];
        foreach ($index['components'] as $component) {
            $components[] = $component['id'];
        }

        $response = [];
        foreach ($scaffolds->getIterator() as $directory) {
            $manifest = JsonHelper::readFile(sprintf(
                '%s/manifest.json',
                $directory->getPathname()
            ));
            if (self::isScaffoldUsable($manifest, $hasLegacyTransformations, $hasNewTransformations, $components)) {
                $manifest['id'] = $directory->getFilename();
                $response[] = $manifest;
            }
        }
        return $response;
    }
}
