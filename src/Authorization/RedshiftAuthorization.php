<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Authorization;

use Keboola\ScaffoldApp\EncryptionClient;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\Options\Components\Configuration;
use Keboola\StorageApi\Workspaces;
use Psr\Log\LoggerInterface;

class RedshiftAuthorization implements AuthorizationInterface
{
    public const NAME = 'provisionedRedshift';

    public function authorize(
        LoggerInterface $logger,
        Configuration $configuration,
        Client $storageClient,
        EncryptionClient $encryptionClient
    ): array {
        $workspace = new Workspaces($storageClient);
        $data = $workspace->createWorkspace([
            'name' => $configuration->getConfigurationId(),
            'backend' => 'redshift',
            'configurationId' => $configuration->getConfigurationId(),
            'component' => $configuration->getComponentId(),
        ]);
        $logger->info(sprintf(
            'Authorized configuration "%s" with redshift credentials "%s".',
            $configuration->getConfigurationId(),
            $data['id']
        ));
        $configData = $configuration->getConfiguration();
        $configData['parameters']['db'] = [
            'host' => $data['connection']['host'],
            'database' => $data['connection']['database'],
            'schema' => $data['connection']['schema'],
            'user' => $data['connection']['user'],
            '#password' => $data['connection']['password'],
            'port' => '5439',
            'driver' => 'redshift',
        ];
        $tokenInfo = $storageClient->verifyToken();
        $configData = $encryptionClient->encryptConfigurationData(
            $configData,
            $configuration->getComponentId(),
            (string) $tokenInfo['owner']['id']
        );
        $configuration->setConfiguration($configData);
        $components = new Components($storageClient);
        $components->updateConfiguration($configuration);
    }
}
