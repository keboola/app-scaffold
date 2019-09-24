<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\ConfigDefinition;

use Keboola\ScaffoldApp\Config;
use Keboola\ScaffoldApp\ConfigDefinition;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ConfigDefinitionTest extends TestCase
{
    public function testGetScaffoldDefinitionClass(): void
    {
        $reflection = new ReflectionClass(ConfigDefinition::class);

        $method = $reflection->getMethod('getScaffoldDefinitionClass');
        $method->setAccessible(true);

        // test without config
        $this->assertNull($method->invokeArgs($reflection->newInstanceWithoutConstructor(), [null]));

        // test with not existing scaffold definition
        $configMock = $this->createMock(Config::class);
        $configMock->method('getScaffoldName')->willReturn('NonExistingScaffold');
        $this->assertNull($method->invokeArgs($reflection->newInstanceWithoutConstructor(), [$configMock]));

        // test with existing scaffold definition
        $configMock = $this->createMock(Config::class);
        $configMock->method('getScaffoldName')->willReturn('ReviewsReviewTrackers');
        $this->assertEquals(
            'Keboola\\Scaffolds\\ReviewsReviewTrackers\\ScaffoldDefinition',
            $method->invokeArgs($reflection->newInstanceWithoutConstructor(), [$configMock])
        );
    }

    public function testValidGetParametersDefinition(): void
    {
        $parameters = <<<JSON
{
    "parameters": {
        "scaffolds":[
            {
                "name":"NonExistingScaffold",
                "parameters":{
                    "notValidatedParameter":"value"
                }
            }
        ]
    }
}
JSON;
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($parameters, JsonEncoder::FORMAT);
        $processor = new Processor();
        $processedConfig = $processor->processConfiguration(new ConfigDefinition(null), [$config]);
        $this->assertSame([
            'parameters' => [
                'scaffolds' => [
                    0 => [
                        'name' => 'NonExistingScaffold',
                        'parameters' => [
                            'notValidatedParameter' => 'value',
                        ],
                    ],
                ],
            ],
        ], $processedConfig);
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
        $definition = new ConfigDefinition(null);
        $processor = new Processor();
        $this->expectException($expectedExceptionClass);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $processor->processConfiguration($definition, [$config]);
    }

    /**
     * @return mixed[][]
     */
    public function provideInvalidConfigs(): array
    {
        $missingNameJson = <<<JSON
{
    "parameters": {
        "scaffolds":[
            {
                "parameters":{
                    "notValidatedParameter":"value"
                }
            }
        ]
    }
}
JSON;
        $emptyNameJson = <<<JSON
{
    "parameters": {
        "scaffolds":[
            {
                "name":"",
                "parameters":{
                    "notValidatedParameter":"value"
                }
            }
        ]
    }
}
JSON;
        $missingParametersJson = <<<JSON
{
    "parameters": {
        "scaffolds":[
            {
                "name":"someName"
            }
        ]
    }
}
JSON;
        $missingScaffoldsJson = <<<JSON
{
    "parameters": {
    }
}
JSON;
        $emptyScaffoldsJson = <<<JSON
{
    "parameters": {
        "scaffolds":[]
    }
}
JSON;
        return [
            'missing name parameter' => [
                $missingNameJson,
                InvalidConfigurationException::class,
                'The child node "name" at path "root.parameters.scaffolds.0" must be configured.',
            ],
            'empty name parameter' => [
                $emptyNameJson,
                InvalidConfigurationException::class,
                'The path "root.parameters.scaffolds.0.name" cannot contain an empty value, but got "".',
            ],
            'missing scaffold parameters' => [
                $missingParametersJson,
                InvalidConfigurationException::class,
                'The child node "parameters" at path "root.parameters.scaffolds.0" must be configured.',
            ],
            'missing scaffold parameter' => [
                $missingScaffoldsJson,
                InvalidConfigurationException::class,
                'The child node "scaffolds" at path "root.parameters" must be configured.',
            ],
            'empty scaffold parameter' => [
                $emptyScaffoldsJson,
                InvalidConfigurationException::class,
                'The path "root.parameters.scaffolds" should have at least 1 element(s) defined.',
            ],
        ];
    }
}
