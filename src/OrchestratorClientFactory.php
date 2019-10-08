<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Keboola\Component\UserException;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\StorageApi\Client as StorageClient;

final class OrchestratorClientFactory
{
    private const SYRUP_SERVICE_ID = 'syrup';

    public static function createForStorageApi(
        StorageClient $storageApiClient
    ): OrchestratorClient {
        $url = '';
        $index = $storageApiClient->indexAction();
        foreach ($index['services'] as $service) {
            if ($service['id'] === self::SYRUP_SERVICE_ID) {
                $url = $service['url'] . '/orchestrator';
                break;
            }
        }
        if (empty($url)) {
            $tokenData = $storageApiClient->verifyToken();
            throw new UserException(sprintf('Syrup not found in %s region', $tokenData['owner']['region']));
        }

        return OrchestratorClient::factory([
            'url' => $url,
            'token' => getenv('KBC_TOKEN'),
        ]);
    }
}
