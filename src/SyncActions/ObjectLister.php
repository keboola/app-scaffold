<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\SyncActions;

use Keboola\ScaffoldApp\Importer\Decorator\DecoratorInterface;
use Keboola\StorageApi\Client as StorageApiClient;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\Metadata;
use Keboola\StorageApi\Options\Components\ListComponentsOptions;
use Keboola\StorageApi\Options\SearchTablesOptions;

class ObjectLister
{
    public static function listObjects(StorageApiClient $storageApiClient): array
    {
        $components = new Components($storageApiClient);
        $listOptions = new ListComponentsOptions();
        $listOptions->setInclude(['configurations']);
        $components = $components->listComponents($listOptions);
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
        $tables = $storageApiClient->searchTables($searchOptions);
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
}
