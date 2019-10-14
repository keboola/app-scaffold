<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer;

use Keboola\Orchestrator\OrchestrationTask;
use Keboola\ScaffoldApp\Importer\Helper;
use Keboola\ScaffoldApp\Importer\OperationImport;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function testConvertTableNameForMetadata(): void
    {
        $import = new OperationImport(
            'scaffoldId',
            'operationId',
            'componentId',
            [],
            new OrchestrationTask()
        );
        self::assertEquals(
            'scaffoldId.internal.out_c-crm_company',
            Helper::convertTableNameForMetadata($import, 'out.c-crm.company')
        );
    }

    public function testConvertToCamelCase(): void
    {
        self::assertEquals('KeboolaExSnowflakeSufix', Helper::convertToCamelCase('keboola.ex-snowflake sufix'));
    }
}
