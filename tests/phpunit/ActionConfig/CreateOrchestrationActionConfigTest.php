<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\ActionConfig;

use Throwable;
use Keboola\ScaffoldApp\ActionConfig\ActionConfigInterface;
use Keboola\ScaffoldApp\ActionConfig\CreateOrchestrationActionConfig;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\TestCase;

class CreateOrchestrationActionConfigTest extends TestCase
{
    private const WORKING_CONFIGURATION = [
        'action' => 'create.orchestration',
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
        $instance = CreateOrchestrationActionConfig::create(self::WORKING_CONFIGURATION, null);
        $this->assertInstanceOf(ActionConfigInterface::class, $instance);
    }

    public function testvalidation(): void
    {
        // missing payload
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Actions create.orchestration missing payload');
        CreateOrchestrationActionConfig::create(['action' => 'action'], null);

        // missing name
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Actions create.orchestration missing name');
        CreateOrchestrationActionConfig::create([
            'action' => 'action',
            'payload' => [],
        ], null);

        // missing tasks
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Actions create.orchestration tasks are empty');
        CreateOrchestrationActionConfig::create([
            'action' => 'action',
            'payload' => ['name' => 'orch01'],
        ], null);

        // empty tasks
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Actions create.orchestration tasks are empty');
        CreateOrchestrationActionConfig::create([
            'action' => 'action',
            'payload' => ['name' => 'orch01', 'tasks' => []],
        ], null);
    }

    public function testPopulateOrchestrationTasksWithConfigurationIds(): void
    {
        $instance = CreateOrchestrationActionConfig::create(self::WORKING_CONFIGURATION, null);

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
            $instance->getTasks()
        );
    }
}
