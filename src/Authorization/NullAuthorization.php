<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Authorization;

use Keboola\ScaffoldApp\EncryptionClient;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Options\Components\Configuration;
use Psr\Log\LoggerInterface;

class NullAuthorization implements AuthorizationInterface
{
    public function authorize(
        LoggerInterface $logger,
        Configuration $configuration,
        Client $storageClient,
        EncryptionClient $encryptionClient
    ): void {
    }
}
