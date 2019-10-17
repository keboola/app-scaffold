<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Authorization;

class AuthorizationFactory
{
    public const AVAILABLE_AUTHORIZATION_METHODS = [SnowflakeAuthorization::NAME, RedshiftAuthorization::NAME];

    public static function getAuthorization(?string $method): AuthorizationInterface
    {
        if (!$method) {
            return new NullAuthorization();
        }
        switch ($method) {
            case SnowflakeAuthorization::NAME:
                return new SnowflakeAuthorization();
            case RedshiftAuthorization::NAME:
                return new RedshiftAuthorization();
            case 'oauth':
                return new OAuthAuthorization();
            default:
                throw new \Exception(sprintf('Invalid authorization method: "%s".', $method));
        }
    }
}
