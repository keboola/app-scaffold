<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\SyncActions;

use Keboola\Component\JsonHelper;
use Keboola\ScaffoldApp\ApiClientStore;
use Symfony\Component\Finder\Finder;

class ListScaffoldsAction
{
    public const NAME = 'listScaffolds';

    public function run(string $scaffoldsDir, ApiClientStore $apiClientStore): array
    {
        $scaffolds = (new Finder())->in($scaffoldsDir)
            ->directories()->depth(0);

        $response = [];
        $scaffoldObjects = ObjectLister::listObjects($apiClientStore->getStorageApiClient(), $apiClientStore->getComponentsApiClient());

        foreach ($scaffolds->getIterator() as $directory) {
            $manifest = JsonHelper::readFile(sprintf(
                '%s/manifest.json',
                $directory->getPathname()
            ));
            $manifest['id'] = $directory->getFilename();
            $manifest['objects'] = $scaffoldObjects[$manifest['id']] ?? [];
            $response[] = $manifest;
        }

        return $response;
    }
}
