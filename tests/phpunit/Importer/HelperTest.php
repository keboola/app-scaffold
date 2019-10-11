<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer;

use Keboola\ScaffoldApp\Importer\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function testConvertToCamelCase(): void
    {
        self::assertEquals('KeboolaExSnowflake', Helper::convertToCamelCase('keboola.ex-snowflake'));
    }
}
