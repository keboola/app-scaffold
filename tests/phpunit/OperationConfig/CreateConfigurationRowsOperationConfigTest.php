<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\OperationConfig;

use Exception;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;
use Keboola\ScaffoldApp\OperationConfig\ConfigRowIterator;
use Keboola\ScaffoldApp\OperationConfig\CreateCofigurationRowsOperationConfig;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\TestCase;

class CreateConfigurationRowsOperationConfigTest extends TestCase
{
    private const WORKING_CONFIGURATION = [
        [
            'name' => 'row01',
            'configuration' => [],
        ],
    ];

    public function testGetIterator(): void
    {
        $instance = CreateCofigurationRowsOperationConfig::create('id', self::WORKING_CONFIGURATION, []);

        $iterator = $instance->getIterator((new Configuration())->setConfigurationId('id'));
        $this->assertInstanceOf(ConfigRowIterator::class, $iterator);
        $this->assertEquals('id', $instance->getOperationReferenceId());
    }

    public function testImplements(): void
    {
        $instance = CreateCofigurationRowsOperationConfig::create('ex01', self::WORKING_CONFIGURATION, []);
        $this->assertInstanceOf(OperationConfigInterface::class, $instance);
    }

    public function testValidateEmptyRows(): void
    {
        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('Operation create.configrows has no rows');
        CreateCofigurationRowsOperationConfig::create('id', [], []);
    }
}
