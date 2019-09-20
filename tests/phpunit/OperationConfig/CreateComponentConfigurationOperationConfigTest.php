<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\OperationConfig;

use Throwable;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;
use Keboola\ScaffoldApp\OperationConfig\CreateComponentConfigurationOperationConfig;
use PHPUnit\Framework\TestCase;

class CreateComponentConfigurationOperationConfigTest extends TestCase
{
    private const WORKING_CONFIGURATION = [
        'action' => 'create.configuration',
        'id' => 'ex01',
        'KBCComponentId' => 'ex01',
        'saveConfigId' => false, // configuration is not saved
        'payload' => [
            'name' => 'ex01',
        ],
    ];

    public function testImplements(): void
    {
        $instance = CreateComponentConfigurationOperationConfig::create(self::WORKING_CONFIGURATION, null);
        $this->assertInstanceOf(OperationConfigInterface::class, $instance);
    }

    public function testValidation(): void
    {
        // misisng component internal id
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.configuration missing id or is empty');
        CreateComponentConfigurationOperationConfig::create([], null);

        // misisng SAPI component id
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.configuration missing KBCComponentId or is empty');
        CreateComponentConfigurationOperationConfig::create(['id' => 'id', 'action' => 'action'], null);

        // missing payload
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.configuration missing payload');
        CreateComponentConfigurationOperationConfig::create([
            'id' => 'id',
            'action' => 'action',
            'KBCComponentId' => 'ex01',
        ], null);

        // missing name
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.configuration payload missing component name');
        CreateComponentConfigurationOperationConfig::create([
            'id' => 'id',
            'action' => 'action',
            'KBCComponentId' => 'ex01',
            'payload' => [],
        ], null);
    }

    public function testMergeParameters(): void
    {
        $instance = CreateComponentConfigurationOperationConfig::create(
            self::WORKING_CONFIGURATION,
            ['ex01' => ['parameters' => ['params1' => ['param1Value']]]]
        );

        $configuration = $instance->getRequestConfiguration();

        $this->assertSame(['parameters' => ['params1' => ['param1Value']]], $configuration->getConfiguration());
    }
}
