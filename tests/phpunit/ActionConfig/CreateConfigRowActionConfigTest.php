<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\ActionConfig;

use Throwable;
use Keboola\ScaffoldApp\ActionConfig\ActionConfigInterface;
use Keboola\ScaffoldApp\ActionConfig\ConfigRowIterator;
use Keboola\ScaffoldApp\ActionConfig\CreateCofigRowActionConfig;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\TestCase;

class CreateConfigRowActionConfigTest extends TestCase
{
    private const WORKING_CONFIGURATION = [
        'action' => 'create.configrows',
        'refConfigId' => 'ex01',
        'rows' => [
            [
                'name' => 'row01',
                'configuration' => [],
            ],
        ],
    ];

    public function testImplements(): void
    {
        $instance = CreateCofigRowActionConfig::create(self::WORKING_CONFIGURATION, null);
        $this->assertInstanceOf(ActionConfigInterface::class, $instance);
    }

    public function testvalidation(): void
    {
        // missing refConfigId
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Actions create.configrows missing refConfigId');
        CreateCofigRowActionConfig::create([], null);

        // missing rows
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Actions create.configrows has no rows');
        CreateCofigRowActionConfig::create([
            'refConfigId' => 'id',
        ], null);

        // empty rows
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Actions create.configrows has no rows');
        CreateCofigRowActionConfig::create([
            'refConfigId' => 'id',
            'rows' => [],
        ], null);
    }

    public function testGetIterator(): void
    {
        $instance = CreateCofigRowActionConfig::create(self::WORKING_CONFIGURATION, null);

        $iterator = $instance->getIterator((new Configuration())->setConfigurationId('id'));
        $this->assertInstanceOf(ConfigRowIterator::class, $iterator);
    }
}
