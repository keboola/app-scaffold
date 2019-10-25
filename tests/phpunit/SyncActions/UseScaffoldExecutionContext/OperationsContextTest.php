<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\SyncActions\UseScaffoldExecutionContext;

use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\OperationsContext;
use PHPUnit\Framework\TestCase;

class OperationsContextTest extends TestCase
{
    public function testGetOperationsToExecute(): void
    {
        $instance = new OperationsContext(['op1', 'op2'], ['op3', 'op4']);
        self::assertSame(['op3', 'op4'], $instance->getOperationsToExecute());
    }

    public function testGetRequiredOperations(): void
    {
        $instance = new OperationsContext(['op1', 'op2'], ['op3', 'op4']);
        self::assertSame(['op1', 'op2'], $instance->getRequiredOperations());
    }
}
