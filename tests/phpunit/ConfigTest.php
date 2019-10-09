<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests;

use Keboola\Component\UserException;
use Keboola\ScaffoldApp\Config;
use Keboola\ScaffoldApp\ScaffoldInputsDefinition;
use Keboola\ScaffoldApp\Tests\mock\ScaffoldDefinitionMock;
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

    public function testGetScaffoldInputsException(): void
    {
        /** @var MockObject|Config $configMock */
        $configMock = self::createPartialMock(Config::class, ['getScaffoldInputDefinition','getParsedInputs']);
        $configMock->method('getScaffoldInputDefinition')->willReturn($this->getMockScaffoldDefinition());
        $configMock->method('getParsedInputs')->willReturn([]);

        self::expectException(UserException::class);
        self::expectExceptionMessage('The child node "ex01" at path "inputs" must be configured.');
        $configMock->getScaffoldInputs();
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
