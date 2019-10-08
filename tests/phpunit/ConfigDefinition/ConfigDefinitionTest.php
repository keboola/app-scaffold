<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\ConfigDefinition;

use Keboola\ScaffoldApp\ConfigDefinition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ConfigDefinitionTest extends TestCase
{
    /**
     * @return mixed[][]
     */
    public function provideInvalidConfigs(): array
    {
        $missingIdJson = <<<JSON
{
    "parameters": {
        "inputs": [
            {
                "id":"testId",
                "values": {
                    "testVal": "value"
                }
            }
        ]
    }
}
JSON;
        $emptyIdJson = <<<JSON
{
    "parameters": {
        "id": "",
        "inputs": [
            {
                "id":"testId",
                "values": {
                    "testVal": "value"
                }
            }
        ]
    }
}
JSON;
        $missingInputIdJson = <<<JSON
{
    "parameters": {
        "id": "test",
        "inputs": [
            {
                "values": {
                    "testVal": "value"
                }
            }
        ]
    }
}
JSON;
        $emptyInputIdJson = <<<JSON
{
    "parameters": {
        "id": "test",
        "inputs": [
            {
                "id":"",
                "values": {
                    "testVal": "value"
                }
            }
        ]
    }
}
JSON;

        $missingInputValuesJson = <<<JSON
{
    "parameters": {
        "id": "test",
        "inputs": [
            {
                "id":""
            }
        ]
    }
}
JSON;
        return [
            'missing scaffold id' => [
                $missingIdJson,
                InvalidConfigurationException::class,
                'The child node "id" at path "root.parameters" must be configured.',
            ],
            'empty scaffold id' => [
                $emptyIdJson,
                InvalidConfigurationException::class,
                'The path "root.parameters.id" cannot contain an empty value, but got "".',
            ],
            'missing input id' => [
                $missingInputIdJson,
                InvalidConfigurationException::class,
                'The child node "id" at path "root.parameters.inputs.0" must be configured.',
            ],
            'empty input id' => [
                $emptyInputIdJson,
                InvalidConfigurationException::class,
                'The path "root.parameters.inputs.0.id" cannot contain an empty value, but got "".',
            ],
            'missing input value' => [
                $missingInputValuesJson,
                InvalidConfigurationException::class,
                'The path "root.parameters.inputs.0.id" cannot contain an empty value, but got "".',
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidConfigs
     */
    public function testInvalidConfigDefinition(
        string $inputConfig,
        string $expectedExceptionClass,
        string $expectedExceptionMessage
    ): void {
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($inputConfig, JsonEncoder::FORMAT);
        self::expectException($expectedExceptionClass);
        self::expectExceptionMessage($expectedExceptionMessage);
        (new Processor())->processConfiguration(new ConfigDefinition(), [$config]);
    }

    public function testValidGetParametersDefinition(): void
    {
        $inputs = <<<JSON
{
    "parameters": {
        "id": "test",
        "inputs": [
            {
                "id":"testId",
                "values": {
                    "testVal": "value"
                }
            }
        ]
    }
}
JSON;
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($inputs, JsonEncoder::FORMAT);
        $processedConfig = (new Processor())->processConfiguration(new ConfigDefinition(), [$config]);
        self::assertSame([
            'parameters' => [
                'id' => 'test',
                'inputs' => [
                    0 => [
                        'id' => 'testId',
                        'values' => [
                            'testVal' => 'value',
                        ],
                    ],
                ],
            ],
        ], $processedConfig);
    }
}
