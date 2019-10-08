<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\ConfigDefinition;

use Keboola\ScaffoldApp\ScaffoldInputsDefinition;
use Keboola\ScaffoldApp\Tests\mock\ScaffoldDefinitionMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ScaffoldInputDefinitionTest extends TestCase
{
    public function testGetScaffoldDefinitionClass(): void
    {
        $reflection = new ReflectionClass(ScaffoldInputsDefinition::class);

        $method = $reflection->getMethod('getScaffoldDefinitionClass');
        $method->setAccessible(true);

        // test without config
        $this->assertNull($method->invokeArgs($reflection->newInstanceWithoutConstructor(), ['NonExistingScaffold']));

        // test with existing scaffold definition
        $this->assertEquals(
            'Keboola\\Scaffolds\\PassThroughTest\\ScaffoldDefinition',
            $method->invokeArgs($reflection->newInstanceWithoutConstructor(), ['PassThroughTest'])
        );
    }

    public function testInvalidValidGetParametersDefinition(): void
    {
        $inputs = [
            'ex01' => [
                'value1' => 'val',
            ],
        ];
        $definition = $this->getMockScaffoldDefinition();

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
        $definition = $this->getMockScaffoldDefinition();

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

    /**
     * @return ScaffoldInputsDefinition|MockObject
     */
    private function getMockScaffoldDefinition()
    {
        /** @var ScaffoldInputsDefinition|MockObject $definition */
        $definition = self::getMockBuilder(ScaffoldInputsDefinition::class)
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->setConstructorArgs(['NotRelevant'])
            ->disallowMockingUnknownTypes()
            ->setMethods(['getScaffoldDefinitionClass'])
            ->getMock();

        $definition->method('getScaffoldDefinitionClass')->willReturn(ScaffoldDefinitionMock::class);
        return $definition;
    }
}
