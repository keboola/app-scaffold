<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation\UseScaffoldExecutionContext;

use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContextLoader;
use PHPUnit\Framework\TestCase;

class ExecutionContextLoaderTest extends TestCase
{
    public function testGetExecutionContext(): void
    {
        $inputs = [
            'connectionWriter' => [
                'parameters' => [],
            ],
            'snowflakeExtractor' => [
                'parameters' => [],
            ],
            'main' => [
                'parameters' => [],
            ],
        ];
        $loader = new ExecutionContextLoader($inputs, __DIR__ . '/../../mock/scaffolds/PassThroughTest');
        $context = $loader->getExecutionContext();

        self::assertArrayHasKey('CreateConfiguration', $context->getOperationsQueue());
        self::assertCount(2, $context->getOperationsQueue()['CreateConfiguration']);
        self::assertArrayHasKey('CreateConfigurationRows', $context->getOperationsQueue());
        self::assertCount(1, $context->getOperationsQueue()['CreateConfigurationRows']);
        self::assertArrayHasKey('CreateOrchestration', $context->getOperationsQueue());
        self::assertCount(1, $context->getOperationsQueue()['CreateOrchestration']);
    }

    public function testGetExecutionContextValidateSchema(): void
    {
        $inputs = [
            'connectionWriter' => [
                'parameters' => [
                    '#token' => 'xxxxx',
                ],
            ],
            'snowflakeExtractor' => [
                'parameters' => [
                    'db' => [
                        'host' => 'xxx',
                        'user' => 'xxx',
                        '#password' => 'xxx',
                        'database' => 'xxx',
                        'schema' => 'xxx',
                        'warehouse' => 'xxx',
                    ],
                ],
            ],
            'main' => [
                'parameters' => [],
            ],
        ];
        $loader = new ExecutionContextLoader($inputs, __DIR__ . '/../../mock/scaffolds/PassThroughTestNoDefinition');
        $context = $loader->getExecutionContext();

        self::assertArrayHasKey('CreateConfiguration', $context->getOperationsQueue());
        self::assertCount(2, $context->getOperationsQueue()['CreateConfiguration']);
        self::assertArrayHasKey('CreateConfigurationRows', $context->getOperationsQueue());
        self::assertCount(1, $context->getOperationsQueue()['CreateConfigurationRows']);
        self::assertArrayHasKey('CreateOrchestration', $context->getOperationsQueue());
        self::assertCount(1, $context->getOperationsQueue()['CreateOrchestration']);
    }

    public function testGetExecutionContextWithoutRequiredOperation(): void
    {
        $inputs = [
            'connectionWriter' => [
                'parameters' => [
                    '#token' => 'xxxxx',
                ],
            ],
            'snowflakeExtractor' => [
                'parameters' => [
                    'db' => [
                        'host' => 'xxx',
                        'user' => 'xxx',
                        '#password' => 'xxx',
                        'database' => 'xxx',
                        'schema' => 'xxx',
                        'warehouse' => 'xxx',
                    ],
                ],
            ],
        ];
        $loader = new ExecutionContextLoader($inputs, __DIR__ . '/../../mock/scaffolds/PassThroughTestNoDefinition');
        $context = $loader->getExecutionContext();

        self::assertArrayHasKey('CreateConfiguration', $context->getOperationsQueue());
        self::assertCount(2, $context->getOperationsQueue()['CreateConfiguration']);
        self::assertArrayHasKey('CreateConfigurationRows', $context->getOperationsQueue());
        self::assertCount(1, $context->getOperationsQueue()['CreateConfigurationRows']);
    }
}
