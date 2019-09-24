<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\OperationConfig;

use Throwable;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;
use Keboola\ScaffoldApp\OperationConfig\ConfigRowIterator;
use Keboola\ScaffoldApp\OperationConfig\CreateCofigRowOperationConfig;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\TestCase;

class CreateConfigRowOperationConfigTest extends TestCase
{
    private const WORKING_CONFIGURATION = [
        'operation' => 'create.configrows',
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
        $instance = CreateCofigRowOperationConfig::create(self::WORKING_CONFIGURATION, []);
        $this->assertInstanceOf(OperationConfigInterface::class, $instance);
    }

    public function testvalidation(): void
    {
        // missing refConfigId
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.configrows missing refConfigId');
        CreateCofigRowOperationConfig::create([], []);

        // missing rows
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.configrows has no rows');
        CreateCofigRowOperationConfig::create([
            'refConfigId' => 'id',
        ], []);

        // empty rows
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Operation create.configrows has no rows');
        CreateCofigRowOperationConfig::create([
            'refConfigId' => 'id',
            'rows' => [],
        ], []);
    }

    public function testGetIterator(): void
    {
        $instance = CreateCofigRowOperationConfig::create(self::WORKING_CONFIGURATION, []);

        $iterator = $instance->getIterator((new Configuration())->setConfigurationId('id'));
        $this->assertInstanceOf(ConfigRowIterator::class, $iterator);
    }
}
