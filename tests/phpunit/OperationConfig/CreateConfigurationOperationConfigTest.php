<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\OperationConfig;

use Throwable;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;
use Keboola\ScaffoldApp\OperationConfig\CreateConfigurationOperationConfig;
use PHPUnit\Framework\TestCase;

class CreateConfigurationOperationConfigTest extends TestCase
{
    private const WORKING_CONFIGURATION = [
        'componentId' => 'ex01',
        'payload' => [
            'name' => 'ex01',
        ],
    ];

    public function testImplements(): void
    {
        $instance = CreateConfigurationOperationConfig::create('ex01', self::WORKING_CONFIGURATION, []);
        $this->assertInstanceOf(OperationConfigInterface::class, $instance);
    }

    public function testMergeParameters(): void
    {
        $instance = CreateConfigurationOperationConfig::create(
            'ex01',
            self::WORKING_CONFIGURATION,
            ['ex01' => ['parameters' => ['params1' => ['param1Value']]]]
        );

        $configuration = $instance->getRequestConfiguration();

        $this->assertSame(['parameters' => ['params1' => ['param1Value']]], $configuration->getConfiguration());
    }

    public function testValidationMissingComponentId(): void
    {
        // missing SAPI component id
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Component Id is missing in operation create.configuration with id "ex01".');
        CreateConfigurationOperationConfig::create(
            'ex01',
            [],
            []
        );
    }

    public function testValidationMissingComponentName(): void
    {
        // missing name
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage(
            'Configuration payload is missing in operation create.configuration with id "ex01".'
        );
        CreateConfigurationOperationConfig::create(
            'ex01',
            [
                'componentId' => 'ex01',
                'payload' => [],
            ],
            []
        );
    }

    public function testValidationMissingPayload(): void
    {
        // missing payload
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage(
            'Configuration payload is missing in operation create.configuration with id "ex01".'
        );
        CreateConfigurationOperationConfig::create(
            'ex01',
            [
                'componentId' => 'ex01',
            ],
            []
        );
    }

    public function testValidationInvalidAuthorization(): void
    {
        // missing payload
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage(
            'Invalid authorization value "invalid" for configuration with id "ex01".'
        );
        CreateConfigurationOperationConfig::create(
            'ex01',
            ['componentId' => 'ex01', 'payload' => ['name' => 'ex01'], 'authorization' => 'invalid'],
            []
        );
    }
}
