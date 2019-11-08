<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\FunctionalTests;

use Keboola\ScaffoldApp\ApiClientStore;
use Keboola\ScaffoldApp\Operation\OperationsConfig;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

trait WorkspaceClearTrait
{
    protected function clearWorkspaceForManifest(
        ApiClientStore $store,
        string $scaffoldFolder
    ): void {
        $configurationsToDelete = [];
        $orchestrationsToDelete = [];
        foreach ([
                     OperationsConfig::CREATE_CONFIGURATION,
                     OperationsConfig::CREATE_ORCHESTRATION,
                 ] as $operationsName
        ) {
            $finder = new Finder();
            $operations = $finder->in(sprintf('%s/operations/%s/', $scaffoldFolder, $operationsName))
                ->files()
                ->depth(0);

            /** @var SplFileInfo $operation */
            foreach ($operations as $operation) {
                if ($operationsName === OperationsConfig::CREATE_ORCHESTRATION) {
                    $orchestrationsToDelete[] = json_decode($operation->getContents(), true)['payload']['name'];
                } else {
                    $configurationsToDelete[] = json_decode($operation->getContents(), true)['payload']['name'];
                }
            }
        }
        $orchestrations = $store->getOrchestrationApiClient()->getOrchestrations();
        foreach ($orchestrations as $orchestration) {
            if (in_array($orchestration['name'], $orchestrationsToDelete)) {
                $store->getOrchestrationApiClient()->deleteOrchestration($orchestration['id']);
            }
        }
        $components = $store->getComponentsApiClient()->listComponents();
        foreach ($components as $component) {
            if ($component['id'] === 'orchestration') {
                continue;
            }
            foreach ($component['configurations'] as $configuration) {
                if (in_array(
                    $configuration['name'],
                    $configurationsToDelete
                )) {
                    $store->getComponentsApiClient()
                        ->deleteConfiguration($component['id'], $configuration['id']);
                }
            }
        }
    }
}
