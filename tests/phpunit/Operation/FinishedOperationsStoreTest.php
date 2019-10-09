<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation;

use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\FinishedOperationsStore;
use PHPUnit\Framework\TestCase;

class FinishedOperationsStoreTest extends TestCase
{
    public function testAdd(): void
    {
        $store = new FinishedOperationsStore();
        $store->add('id', CreateConfigurationOperation::class, ['data']);
        self::assertSame(['data'], $store->getOperationData('id'));
        self::assertCount(1, $store->getOperations());
    }

    public function testGetOperationDataMissingReference(): void
    {
        $store = new FinishedOperationsStore();
        $store->add('id', CreateConfigurationOperation::class, ['data']);

        self::expectException(\Throwable::class);
        self::expectExceptionMessage('Operation "NoId" was not finished or it\'s wrongly configured.');
        $store->getOperationData('NoId');
    }
}
