<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

final class Helper
{
    public static function convertToCamelCase(string $string): string
    {
        foreach (['-', '.'] as $delimiter) {
            $string = ucwords($string, $delimiter);
        }
        return str_replace(['-', '.'], '', $string);
    }

    public static function generateRandomSufix(): string
    {
        return bin2hex(random_bytes(2));
    }
}
