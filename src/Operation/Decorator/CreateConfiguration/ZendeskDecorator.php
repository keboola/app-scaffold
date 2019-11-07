<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation\Decorator\CreateConfiguration;

use Keboola\StorageApi\Options\Components\Configuration;

class ZendeskDecorator implements ConfigurationDecoratorInterface
{
    public static function supports(Configuration $configuration): bool
    {
        return $configuration->getComponentId() === 'keboola.ex-zendesk';
    }

    public function getDecoratedConfiguration(
        Configuration $configuration
    ): Configuration {
        $config = $configuration->getConfiguration();
        $config['parameters']['config']['username'] =
            sprintf('%s/token', $config['parameters']['config']['usermail']);
        $configuration->setConfiguration($config);

        return $configuration;
    }
}
