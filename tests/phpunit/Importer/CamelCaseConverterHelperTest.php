<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer;

use Keboola\ScaffoldApp\Importer\CamelCaseConverterHelper;
use PHPUnit\Framework\TestCase;

class CamelCaseConverterHelperTest extends TestCase
{
    public function testConvertToCamelCase(): void
    {
        $converted = CamelCaseConverterHelper::convertToCamelCase(
            'keboola.ex-snowflake sufix_underscore',
            CamelCaseConverterHelper::STOP_WORDS_NO_UNDERSCORE
        );
        self::assertEquals('keboolaExSnowflakeSufix_underscore', $converted);

        $converted = CamelCaseConverterHelper::convertToCamelCase(
            'keboola.ex-snowflake sufix_underscore',
            CamelCaseConverterHelper::STOP_WORDS_NO_DOT
        );
        self::assertEquals('keboola.exSnowflakeSufixUnderscore', $converted);

        $converted = CamelCaseConverterHelper::convertToCamelCase(
            'keboola.ex-snowflake sufix_underscore'
        );
        self::assertEquals('keboola.exSnowflakeSufixUnderscore', $converted);
    }
}
