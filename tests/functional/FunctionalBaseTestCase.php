<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\FunctionalTests;

use Exception;
use Keboola\Component\JsonHelper;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\ScaffoldApp\Component;
use Keboola\ScaffoldApp\EncryptionClient;
use Keboola\ScaffoldApp\Operation\ExecutionContext;
use Keboola\ScaffoldApp\Operation\FinishedOperationsStore;
use Keboola\ScaffoldApp\OrchestratorClientFactory;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldAction;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\Components as ComponentsApiClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;

abstract class FunctionalBaseTestCase extends TestCase
{
    protected function clearWorkspace(): void
    {
        $orchestrationApiClient = $this->createOrchestrationApiClient();
        $orchestrations = $orchestrationApiClient->getOrchestrations();
        foreach ($orchestrations as $orchestration) {
            if ($orchestration['name'] === 'orch01') {
                $orchestrationApiClient->deleteOrchestration($orchestration['id']);
            }
        }
        $componentApiClient = new Components($this->createStorageApiClient());
        $components = $componentApiClient->listComponents();
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
                    $componentApiClient->deleteConfiguration($component['id'], $configuration['id']);
                }
            }
        }
    }

    protected function createOrchestrationApiClient(): OrchestratorClient
    {
        return OrchestratorClientFactory::createForStorageApi($this->createStorageApiClient());
    }

    protected function createStorageApiClient(): Client
    {
        return new Client(
            [
                'token' => getenv('KBC_TOKEN'),
                'url' => getenv('KBC_URL'),
                'logger' => new NullLogger(),
            ]
        );
    }

    protected function createComponentsApiClient(): ComponentsApiClient
    {
        return new ComponentsApiClient($this->createStorageApiClient());
    }

    protected function createEncryptionApiClient(): EncryptionClient
    {
        return EncryptionClient::createForStorageApi($this->createStorageApiClient());
    }

    protected function exportTestScaffold(
        string $scaffoldId,
        array $inputParameters
    ): ExecutionContext {
        $scaffoldFolder = __DIR__ . '/../phpunit/mock/scaffolds/' . $scaffoldId;
        $manifest = JsonHelper::readFile(sprintf('%s/manifest.json', $scaffoldFolder));

        $executionContext = new ExecutionContext(
            $manifest,
            $inputParameters,
            $scaffoldFolder,
            new NullLogger
        );
        $executionContext->loadOperations();
        $executionContext->loadOperationsFiles();

        $action = new UseScaffoldAction($executionContext);
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
    }
}
