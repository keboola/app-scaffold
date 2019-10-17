<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Authorization;

class AuthorizationFactory
{
    public static function getAvailableMethods(): array
    {
        return ['provisionedSnowflake', 'provisionedRedshift', 'oAuth'];
    }

    public static function getAuthorization(?string $method): AuthorizationInterface
    {
        if (!$method) {
            return new NullAuthorization();
        }
        switch ($method) {
            case 'provisionedSnowflake':
                return new SnowflakeAuthorization();
            case 'provisionedRedshift':
                return new RedshiftAuthorization();
            default:
                throw new \Exception(sprintf('Invalid authorization method: "%s".', $method));
        }
    }
}
