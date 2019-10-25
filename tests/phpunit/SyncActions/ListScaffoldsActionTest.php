<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\SyncActions;

use Keboola\ScaffoldApp\SyncActions\ListScaffoldsAction;
use Keboola\ScaffoldApp\Tests\Operation\BaseOperationTestCase;

class ListScaffoldsActionTest extends BaseOperationTestCase
{
    public function testAction(): void
    {
        $action = new ListScaffoldsAction();
        $response = $action->run(__DIR__ . '/../mock/scaffolds', $this->getApiClientStore());
        self::assertCount(2, $response);
        foreach ($response as $scaffold) {
            self::assertArrayHasKey('id', $scaffold);
        }
    }
}
