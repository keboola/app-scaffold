<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer;

use Keboola\Orchestrator\OrchestrationTask;
use Keboola\ScaffoldApp\Importer\OperationImport;

class OperationImportTest extends ImporterBaseTestCase
{
    public function testOperationImport(): void
    {
        $task = $this->getExampleOrchstrationTask();
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
            'scaffoldId',
            'operationId',
            'keboola.component',
            $payload,
            $task,
            $configurationRows
        );

        self::assertEquals('scaffoldId', $operationImport->getScaffoldId());
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
