<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\ActionConfig;

use Throwable;
use Keboola\ScaffoldApp\ActionConfig\ActionConfigInterface;
use Keboola\ScaffoldApp\ActionConfig\CreateComponentConfigurationActionConfig;
use PHPUnit\Framework\TestCase;

class CreateComponentConfigurationActionConfigTest extends TestCase
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
        $instance = CreateComponentConfigurationActionConfig::create(self::WORKING_CONFIGURATION, null);
        $this->assertInstanceOf(ActionConfigInterface::class, $instance);
    }

    public function testValidation(): void
    {
        // misisng SAPI component id
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Actions create.configuration missing KBCComponentId');
        CreateComponentConfigurationActionConfig::create(['action' => 'action'], null);

        // missing payload
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Actions create.configuration missing payload');
        CreateComponentConfigurationActionConfig::create(['action' => 'action', 'KBCComponentId' => 'ex01',], null);

        // missing name
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Actions create.configuration payload missing component name');
        CreateComponentConfigurationActionConfig::create([
            'action' => 'action',
            'KBCComponentId' => 'ex01',
            'payload' => [],
        ], null);
    }

    public function testMergeParameters(): void
    {
        $instance = CreateComponentConfigurationActionConfig::create(
            self::WORKING_CONFIGURATION,
            ['ex01' => ['parameters' => ['params1' => ['param1Value']]]]
        );

        $configuration = $instance->getRequestConfiguration();

        $this->assertSame(['parameters' => ['params1' => ['param1Value']]], $configuration->getConfiguration());
    }
}
