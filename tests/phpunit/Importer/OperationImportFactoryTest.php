<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer;

use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\OperationImportFactory;

class OperationImportFactoryTest extends ImporterBaseTestCase
{
    public function testCreateOperationImport(): void
    {
        $task = $this->getExampleOrchstrationTask();

        $operationImport = OperationImportFactory::createOperationImport(
            [
                'name' => '',
                'configuration' => [

                ],
                'rows' => [],
            ],
            $task,
            'scaffoldId'
        );

        self::assertInstanceOf(OperationImport::class, $operationImport);
        self::assertSame([
            'name' => '',
            'configuration' => [],
        ], $operationImport->getPayload());
    }
}
