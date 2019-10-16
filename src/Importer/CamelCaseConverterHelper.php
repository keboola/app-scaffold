<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

final class CamelCaseConverterHelper
{
    public const STOP_WORDS_FULL = ['-', '.', ' ', '_'];
    public const STOP_WORDS_NO_UNDERSCORE = ['-', '.', ' '];

    public static function convertToCamelCase(
        string $string,
        array $stopWords = self::STOP_WORDS_FULL
    ): string {
        foreach ($stopWords as $delimiter) {
            $string = ucwords($string, $delimiter);
        }
        return lcfirst(str_replace($stopWords, '', $string));
    }
}
