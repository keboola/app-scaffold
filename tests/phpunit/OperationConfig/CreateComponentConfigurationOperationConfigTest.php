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
        'operation' => 'create.configuration',
        'id' => 'ex01',
        'KBCComponentId' => 'ex01',
        'payload' => [
            'name' => 'ex01',
        ],
    ];

    public function testImplements(): void
    {
        $instance = CreateComponentConfigurationOperationConfig::create(self::WORKING_CONFIGURATION, []);
        $this->assertInstanceOf(OperationConfigInterface::class, $instance);
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

    public function testValidationMissingComponentId(): void
    {
        // misisng SAPI component id
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Component Id is missing in operation create.configuration with id "id1".');
        CreateComponentConfigurationOperationConfig::create([
            'id' => 'id1',
            'action' => 'action',
        ], []);
    }

    public function testValidationMissingComponentName(): void
    {
        // missing name
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage(
            'Configuration payload is missing in operation create.configuration with id "id1".'
        );
        CreateComponentConfigurationOperationConfig::create([
            'id' => 'id1',
            'action' => 'action',
            'KBCComponentId' => 'ex01',
            'payload' => [],
        ], []);
    }

    public function testValidationMissingId(): void
    {
        // misisng component internal id
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation ID is missing in create.configuration operation "[]".');
        CreateComponentConfigurationOperationConfig::create([], []);
    }

    public function testValidationMissingPayload(): void
    {
        // missing payload
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage(
            'Configuration payload is missing in operation create.configuration with id "id".'
        );
        CreateComponentConfigurationOperationConfig::create([
            'id' => 'id',
            'action' => 'action',
            'KBCComponentId' => 'ex01',
        ], []);
    }
}
