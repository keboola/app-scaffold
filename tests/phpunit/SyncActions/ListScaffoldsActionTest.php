<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\SyncActions;

use Keboola\ScaffoldApp\SyncActions\ListScaffoldsAction;
use PHPUnit\Framework\TestCase;

class ListScaffoldsActionTest extends TestCase
{
    public function testAction(): void
    {
        $action = new ListScaffoldsAction();
        $response = $action->run(__DIR__ . '/../mock/scaffolds');
        self::assertCount(2, $response);
        foreach ($response as $scaffold) {
            self::assertArrayHasKey('id', $scaffold);
        }
    }
}
