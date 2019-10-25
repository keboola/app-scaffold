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
                'snowflakeExtractor' => [
                    'val2' => 'val',
                ],
                'connectionWriter' => [],
                'main' => [],
            ],
            __DIR__ . '/../mock/scaffolds/PassThroughTest'
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
