<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\OperationConfig;

use Keboola\ScaffoldApp\Operation\CreateOrchestrationOperation;
use Keboola\ScaffoldApp\Operation\ExecutionContext;
use Keboola\ScaffoldApp\Operation\FinishedOperationsStore;
use Psr\Log\NullLogger;
use Throwable;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;
use Keboola\ScaffoldApp\OperationConfig\CreateOrchestrationOperationConfig;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\TestCase;

class CreateOrchestrationOperationConfigTest extends TestCase
{
    private const WORKING_CONFIGURATION = [
        'payload' => [
            'name' => 'orch01',
            'tasks' => [
                [
                    'operationReferenceId' => 'ex01',
                ],
            ],
        ],
    ];

    public function testImplements(): void
    {
        $instance = CreateOrchestrationOperationConfig::create('operation_id', self::WORKING_CONFIGURATION, []);
        $this->assertInstanceOf(OperationConfigInterface::class, $instance);
    }

    public function testPopulateOrchestrationTasksWithConfigurationIds(): void
    {
        $instance = CreateOrchestrationOperationConfig::create('operation_id', self::WORKING_CONFIGURATION, []);

        $exectutionContext = new ExecutionContext([], [], '', new NullLogger);
        $exectutionContext->finishOperation(
            'ex01',
            CreateOrchestrationOperation::class,
            (new Configuration())->setConfigurationId('id')
        );

        $instance->populateOrchestrationTasksWithConfigurationIds($exectutionContext);

        $this->assertSame(
            [
                [
                    'operationReferenceId' => 'ex01',
                    'actionParameters' => [
                        'config' => 'id',
                    ],
                ],
            ],
            $instance->getPayload()['tasks']
        );
    }

    public function testValidationEmptyTasks(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.orchestration tasks are empty');
        CreateOrchestrationOperationConfig::create('operation_id', [
            'payload' => ['name' => 'orch01', 'tasks' => []],
        ], []);
    }

    public function testValidationMissingName(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.orchestration missing name');
        CreateOrchestrationOperationConfig::create('operation_id', [
            'payload' => [
                'tasks' => [],
            ],
        ], []);
    }

    public function testValidationMissingPayload(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.orchestration missing payload');
        CreateOrchestrationOperationConfig::create('operation_id', [], []);
    }

    public function testValidationMissingTasks(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.orchestration tasks are empty');
        CreateOrchestrationOperationConfig::create('operation_id', [
            'payload' => ['name' => 'orch01'],
        ], []);
    }
}
