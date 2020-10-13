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
        $scaffoldObjects = ObjectLister::listObjects(
            $apiClientStore->getStorageApiClient(),
            $apiClientStore->getComponentsApiClient()
        );
        $scaffolds = ObjectLister::listScaffolds(
            $apiClientStore->getStorageApiClient(),
            $scaffoldsDir
        );
        $response = [];
        foreach ($scaffolds as $scaffold) {
            $scaffold['objects'] = $scaffoldObjects[$scaffold['id']] ?? [];
            $response[] = $scaffold;
        }
        return $response;
    }
}
