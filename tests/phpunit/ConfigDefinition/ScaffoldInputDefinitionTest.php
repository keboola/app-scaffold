<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\ConfigDefinition;

use Keboola\ScaffoldApp\ScaffoldInputsDefinition;
use Keboola\ScaffoldApp\Tests\mock\ScaffoldDefinitionMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ScaffoldInputDefinitionTest extends TestCase
{

    public function testInvalidValidGetParametersDefinition(): void
    {
        $inputs = [
            'ex01' => [
                'value1' => 'val',
            ],
        ];
        $definition = new ScaffoldInputsDefinition(new ScaffoldDefinitionMock);

        self::expectException(InvalidConfigurationException::class);
        self::expectExceptionMessage('The child node "wr01" at path "inputs" must be configured.');
        (new Processor())->processConfiguration($definition, [$inputs]);
    }

    public function testValidGetParametersDefinition(): void
    {
        $inputs = [
            'ex01' => [
                'value1' => 'val',
            ],
            'wr01' => [
                'value2' => 'val',
            ],
        ];
        $definition = new ScaffoldInputsDefinition(new ScaffoldDefinitionMock);

        $processedConfig = (new Processor())->processConfiguration($definition, [$inputs]);
        self::assertSame([
            'ex01' => [
                'value1' => 'val',
            ],
            'wr01' => [
                'value2' => 'val',
            ],
        ], $processedConfig);
    }
}
