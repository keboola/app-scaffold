<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\FunctionalTests;

use Keboola\ScaffoldApp\ApiClientStore;
use Keboola\ScaffoldApp\SyncActions\ObjectLister;

trait WorkspaceClearTrait
{
    protected function clearWorkspace(
        ApiClientStore $store,
        string $scaffoldId
    ): void {
        $objects = ObjectLister::listObjects($store->getStorageApiClient(), $store->getComponentsApiClient());
        if (empty($objects[$scaffoldId])) {
            return;
        }
        if (!empty($objects[$scaffoldId]['configurations'])) {
            foreach ($objects[$scaffoldId]['configurations'] as $configuration) {
                $store->getComponentsApiClient()
                    ->deleteConfiguration($configuration['componentId'], $configuration['configurationId']);
            }
        };
        $bucketsToDrop = [];
        if (!empty($objects[$scaffoldId]['tables'])) {
            foreach ($objects[$scaffoldId]['tables'] as $tableId) {
                $table = $store->getStorageApiClient()
                    ->getTable($tableId);
                if (!in_array($table['bucket']['id'], $bucketsToDrop)) {
                    $bucketsToDrop[] = $table['bucket']['id'];
                }
                $store->getStorageApiClient()
                    ->dropTable($tableId);
            }
        };
        if (!empty($bucketsToDrop)) {
            foreach ($bucketsToDrop as $bucketId) {
                $store->getStorageApiClient()
                    ->dropBucket($bucketId);
            }
        };
    }
}
