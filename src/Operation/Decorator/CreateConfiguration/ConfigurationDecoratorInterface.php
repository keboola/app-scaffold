<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation\Decorator\CreateConfiguration;

use Keboola\StorageApi\Options\Components\Configuration;

interface ConfigurationDecoratorInterface
{
    public function getDecoratedConfiguration(
        Configuration $configuration
    ): Configuration;

    public function supports(Configuration $configuration): bool;
}
