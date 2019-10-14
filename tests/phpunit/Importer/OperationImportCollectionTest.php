<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer;

use Exception;
use Keboola\Orchestrator\OrchestrationTask;
use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\OperationImportCollection;
use PHPUnit\Framework\TestCase;

class OperationImportCollectionTest extends TestCase
{
    public function testAddImportedOperation(): void
    {
        $collection = new OperationImportCollection();
        $collection->addImportedOperation(
            new OperationImport('scaffoldId', 'operationId', 'componentId', [], new OrchestrationTask())
        );

        self::assertCount(1, $collection->getImportedOperations());

        self::expectException(Exception::class);
        self::expectExceptionMessage('Duplicite operation name. Operation "operationId" already exists.');
        $collection->addImportedOperation(
            new OperationImport('scaffoldId', 'operationId', 'componentId', [], new OrchestrationTask())
        );
    }
}
