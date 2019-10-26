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
        $clientMock = $this->getMockStorageApiClient();
        $clientMock->method('searchTables')->willReturn([]);
        $componentMock = $this->getMockComponentsApiClient();
        $componentMock->method('listComponents')->willReturn([]);
        $response = $action->run(
            __DIR__ . '/../mock/scaffolds',
            $this->getApiClientStore($clientMock, $componentMock)
        );
        self::assertCount(2, $response);
        foreach ($response as $scaffold) {
            self::assertArrayHasKey('id', $scaffold);
        }
    }
}
