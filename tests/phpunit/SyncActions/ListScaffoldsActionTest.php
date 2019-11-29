<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\SyncActions;

use Keboola\ScaffoldApp\SyncActions\ListScaffoldsAction;
use Keboola\ScaffoldApp\Tests\Operation\BaseOperationTestCase;
use Symfony\Component\Finder\Finder;

class ListScaffoldsActionTest extends BaseOperationTestCase
{
    public const SCAFFOLD_DIR = __DIR__ . '/../mock/scaffolds';

    public function testAction(): void
    {
        $action = new ListScaffoldsAction();
        $clientMock = $this->getMockStorageApiClient();
        $clientMock->method('searchTables')->willReturn([]);
        $componentMock = $this->getMockComponentsApiClient();
        $componentMock->method('listComponents')->willReturn([]);
        $response = $action->run(
            self::SCAFFOLD_DIR,
            $this->getApiClientStore($clientMock, $componentMock)
        );
        $scaffolds = (new Finder())->in(self::SCAFFOLD_DIR)
            ->directories()->depth(0);

        self::assertCount(count($scaffolds), $response);
        foreach ($response as $scaffold) {
            self::assertArrayHasKey('id', $scaffold);
        }
    }
}
