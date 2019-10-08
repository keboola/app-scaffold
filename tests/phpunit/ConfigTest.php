<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests;

use Keboola\ScaffoldApp\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testGetParsedInputs(): void
    {
        /** @var MockObject|Config $configMock */
        $configMock = self::createPartialMock(Config::class, ['getParameters']);
        $configMock->method('getParameters')->willReturn([
            'inputs' => [
                [
                    'id' => 'ex01',
                    'values' => [
                        'val1' => 'val',
                    ],
                ],
            ],
        ]);

        self::assertSame([
            'ex01' => [
                'val1' => 'val',
            ],
        ], $configMock->getParsedInputs());
    }

    public function testGetScaffoldName(): void
    {
        /** @var MockObject|Config $configMock */
        $configMock = self::createPartialMock(Config::class, ['getParameters']);
        $configMock->method('getParameters')->willReturn([
            'id' => 'scaffold',
        ]);

        self::assertEquals('scaffold', $configMock->getScaffoldName());
    }
}
