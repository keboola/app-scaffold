<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer;

use Keboola\Orchestrator\OrchestrationTask;
use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\OrchestrationTaskFactory;
use PHPUnit\Framework\TestCase;

class OperationImportTest extends TestCase
{
    public function testOperationImport(): void
    {
        $task = OrchestrationTaskFactory::createTaskFromResponse([
            'component' => 'keboola.component',
            'action' => 'run',
            'actionParameters' => [
            ],
            'continueOnFailure' => false,
            'active' => true,
            'timeoutMinutes' => null,
            'phase' => 1,
        ]);
        self::assertInstanceOf(OrchestrationTask::class, $task);

        $configurationRows = [
            [
                'configuration' => [],
                'description' => 'desc',
                'name' => 'rowName',
                'randomKey' => 'keyValue',
            ],
        ];

        $payload = [
            'name' => 'ComponentName',
            'configuration' => [],
        ];

        $operationImport = new OperationImport(
            'operationId',
            'keboola.component',
            $payload,
            $task,
            $configurationRows
        );

        self::assertEquals('keboola.component', $operationImport->getComponentId());
        self::assertEquals($configurationRows, $operationImport->getConfigurationRows());
        self::assertEquals([
            [
                'configuration' => [],
                'description' => 'desc',
                'name' => 'rowName',
            ],
        ], $operationImport->getConfigurationRowsJsonArray());
        self::assertEquals([
            'componentId' => 'keboola.component',
            'payload' => $payload,
        ], $operationImport->getCreateConfigurationJsonArray());
        self::assertEquals([
            'component' => 'keboola.component',
            'operationReferenceId' => 'operationId',
            'action' => 'run',
            'timeoutMinutes' => null,
            'active' => true,
            'continueOnFailure' => false,
            'phase' => 1,
        ], $operationImport->getOrchestrationTaskJsonArray());
    }
}
