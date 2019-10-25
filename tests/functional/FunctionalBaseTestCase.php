<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\FunctionalTests;

use Exception;
use Keboola\ScaffoldApp\ApiClientStore;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContext;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContextLoader;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldAction;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

abstract class FunctionalBaseTestCase extends TestCase
{
    /**
     * @var ApiClientStore
     */
    protected $apiClients;

    protected function clearWorkspace(): void
    {
        $orchestrations = $this->apiClients->getOrchestrationApiClient()->getOrchestrations();
        foreach ($orchestrations as $orchestration) {
            if ($orchestration['name'] === 'orch01') {
                $this->apiClients->getOrchestrationApiClient()->deleteOrchestration($orchestration['id']);
            }
        }
        $components = $this->apiClients->getComponentsApiClient()->listComponents();
        foreach ($components as $component) {
            if ($component['id'] === 'orchestration') {
                continue;
            }
            foreach ($component['configurations'] as $configuration) {
                if (in_array(
                    $configuration['name'],
                    [
                        'ex01',
                    ]
                )) {
                    $this->apiClients->getComponentsApiClient()
                        ->deleteConfiguration($component['id'], $configuration['id']);
                }
            }
        }
    }

    protected function exportTestScaffold(
        string $scaffoldId,
        array $inputParameters
    ): ExecutionContext {
        $scaffoldFolder = __DIR__ . '/../phpunit/mock/scaffolds/' . $scaffoldId;
        $loader = new ExecutionContextLoader($inputParameters, $scaffoldFolder);

        $executionContext = $loader->getExecutionContext();

        $action = new UseScaffoldAction(
            $executionContext,
            new ApiClientStore(new NullLogger()),
            new NullLogger
        );
        $action();

        return $executionContext;
    }

    protected function setUp(): void
    {
        if (getenv('KBC_TOKEN') === false) {
            throw new Exception('Env variable "KBC_TOKEN" must be set.');
        }
        if (getenv('KBC_URL') === false) {
            throw new Exception('Env variable "KBC_URL" must be set.');
        }

        $this->apiClients = new ApiClientStore(new NullLogger());
    }
}
