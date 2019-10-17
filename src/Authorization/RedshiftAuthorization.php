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
    public function authorize(
        LoggerInterface $logger,
        Configuration $configuration,
        Client $storageClient,
        EncryptionClient $encryptionClient
    ) {
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
        $configData['parameters']['db']['host'] = $data['connection']['host'];
        $configData['parameters']['db']['database'] = $data['connection']['database'];
        $configData['parameters']['db']['schema'] = $data['connection']['schema'];
        $configData['parameters']['db']['user'] = $data['connection']['user'];
        $configData['parameters']['db']['#password'] = $data['connection']['password'];
        $configData['parameters']['db']['port'] = '5439';
        $configData['parameters']['db']['driver'] = 'redshift';
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
