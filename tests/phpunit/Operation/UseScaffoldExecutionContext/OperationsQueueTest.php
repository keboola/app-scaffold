<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation\UseScaffoldExecutionContext;

use Keboola\ScaffoldApp\Operation\CreateOrchestrationOperation;
use Keboola\ScaffoldApp\Operation\UseScaffoldExecutionContext\OperationsQueue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

class OperationsQueueTest extends TestCase
{
    public function testAddOperation(): void
    {
        $queue = new OperationsQueue();
        $queue->addOperation('id', new SplFileInfo('fileName', '', ''));
        self::assertArrayHasKey('id', $queue->getOperationsQueue());
        self::assertCount(1, $queue->getOperationsQueue()['id']);
    }

    public function testFinishOperation(): void
    {
        $queue = new OperationsQueue();
        $queue->finishOperation('id', CreateOrchestrationOperation::class, ['data']);
        self::assertSame(['data'], $queue->getFinishedOperationData('id'));
        self::assertCount(1, $queue->getFinishedOperations());
    }
}
