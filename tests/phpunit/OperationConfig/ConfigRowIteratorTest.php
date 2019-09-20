<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\OperationConfig;

use Iterator;
use Keboola\ScaffoldApp\OperationConfig\ConfigRowIterator;
use Keboola\StorageApi\Options\Components\Configuration;
use Keboola\StorageApi\Options\Components\ConfigurationRow;
use PHPUnit\Framework\TestCase;

class ConfigRowIteratorTest extends TestCase
{
    private const ROWS = [
        [
            'name' => 'row01',
            'configuration' => [],
        ],
        [
            'name' => 'row02',
            'configuration' => [],
        ],
    ];

    public function testImplements(): void
    {
        $instance = new ConfigRowIterator(self::ROWS, new Configuration);
        $this->assertInstanceOf(Iterator::class, $instance);
    }

    public function testIteratorCurrent(): void
    {
        $instance = new ConfigRowIterator(self::ROWS, new Configuration);

        foreach ($instance as $item) {
            $this->assertInstanceOf(ConfigurationRow::class, $item);
        }
    }
}
