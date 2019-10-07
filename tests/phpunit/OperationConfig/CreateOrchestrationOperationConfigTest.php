<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\OperationConfig;

use Throwable;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;
use Keboola\ScaffoldApp\OperationConfig\CreateOrchestrationOperationConfig;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\TestCase;

class CreateOrchestrationOperationConfigTest extends TestCase
{
    private const WORKING_CONFIGURATION = [
        'operation' => 'create.orchestration',
        'payload' => [
            'name' => 'orch01',
            'tasks' => [
                [
                    'refConfigId' => 'ex01',
                ],
            ],
        ],
    ];

    public function testImplements(): void
    {
        $instance = CreateOrchestrationOperationConfig::create(self::WORKING_CONFIGURATION, []);
        $this->assertInstanceOf(OperationConfigInterface::class, $instance);
    }

    public function testvalidation(): void
    {
        // missing payload
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.orchestration missing payload');
        CreateOrchestrationOperationConfig::create(['action' => 'action'], []);

        // missing name
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.orchestration missing name');
        CreateOrchestrationOperationConfig::create([
            'action' => 'action',
            'payload' => [],
        ], []);

        // missing tasks
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.orchestration tasks are empty');
        CreateOrchestrationOperationConfig::create([
            'action' => 'action',
            'payload' => ['name' => 'orch01'],
        ], []);

        // empty tasks
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.orchestration tasks are empty');
        CreateOrchestrationOperationConfig::create([
            'action' => 'action',
            'payload' => ['name' => 'orch01', 'tasks' => []],
        ], []);
    }

    public function testPopulateOrchestrationTasksWithConfigurationIds(): void
    {
        $instance = CreateOrchestrationOperationConfig::create(self::WORKING_CONFIGURATION, []);

        $instance->populateOrchestrationTasksWithConfigurationIds(
            [
                'ex01' => (new Configuration())->setConfigurationId('id'),
            ]
        );

        $this->assertSame(
            [
                [
                    'refConfigId' => 'ex01',
                    'actionParameters' => [
                        'config' => 'id',
                    ],
                ],
            ],
            $instance->getPayload()['tasks']
        );
    }
}
