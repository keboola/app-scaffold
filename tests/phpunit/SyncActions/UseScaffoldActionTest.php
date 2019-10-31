<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\SyncActions;

use Keboola\ScaffoldApp\SyncActions\UseScaffoldAction;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContextLoader;
use Keboola\ScaffoldApp\Tests\Operation\BaseOperationTestCase;
use Psr\Log\NullLogger;

class UseScaffoldActionTest extends BaseOperationTestCase
{
    public function testAction(): void
    {
        $loader = new ExecutionContextLoader(
            [
                'connectionWriter' => [
                    'parameters' => [
                        '#token' => 'dummy',
                    ],
                ],
                'snowflakeExtractor' => [
                    'parameters' => [
                        'db' => [
                            'host' => 'dummy',
                            'user' => 'dummy',
                            '#password' => 'dummy',
                            'schema' => 'dummy',
                            'database' => 'dummy',
                            'warehouse' => 'dummy',
                        ],
                    ],
                ],
                'main' => [
                    'parameters' => [
                        'values' => null,
                    ],
                ],
            ],
            __DIR__ . '/../mock/scaffolds/PassThroughTestNoDefinition'
        );
        $action = new UseScaffoldAction($loader->getExecutionContext(), $this->getApiClientStore(), new NullLogger());
        $response = $action->run();
        foreach ($response as $finishedOperation) {
            self::assertArrayHasKey('id', $finishedOperation);
            self::assertArrayHasKey('configurationId', $finishedOperation);
            self::assertArrayHasKey('userActions', $finishedOperation);
        }
    }
}
