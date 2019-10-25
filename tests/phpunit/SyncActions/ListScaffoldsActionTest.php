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
        $response = $action(__DIR__ . '/../mock/scaffolds');
        self::assertCount(2, $response);
        self::assertEquals('PassThroughTestNoDefinition', $response[0]['id']);
        self::assertEquals('PassThroughTest', $response[1]['id']);
    }
}
