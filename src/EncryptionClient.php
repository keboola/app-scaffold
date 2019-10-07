<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use GuzzleHttp\Client;
use Keboola\Component\UserException;
use Keboola\StorageApi\Client as StorageClient;

class EncryptionClient extends Client
{
    private const ENCRYPTION_SERVICE_ID = 'encryption';

    /**
     * @var StorageClient
     */
    private $storageApiClient;

    public function __construct(
        string $encryptionApiUrl,
        StorageClient $storageApiClient
    ) {
        parent::__construct(['base_uri' => $encryptionApiUrl]);
        $this->storageApiClient = $storageApiClient;
    }

    public static function createForStorageApi(StorageClient $storageApiClient): self
    {
        $index = $storageApiClient->indexAction();
        foreach ($index['services'] as $service) {
            if ($service['id'] === self::ENCRYPTION_SERVICE_ID) {
                return new self($service['url'], $storageApiClient);
            }
        }
        $tokenData = $storageApiClient->verifyToken();
        throw new UserException(sprintf('Encryption service not found in %s region', $tokenData['owner']['region']));
    }

    public function encryptConfigurationData(
        array $data,
        string $componentId,
        string $projectId
    ): array {
        $response = $this->request(
            'POST',
            'encrypt',
            [
                'headers' =>
                    [
                        'X-StorageApi-Token' => $this->storageApiClient->getTokenString(),
                        'Content-Type' => 'application/json',
                    ],
                'query' => [
                    'componentId' => $componentId,
                    'projectId' => $projectId,
                ],
                'body' => json_encode($data),
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }
}
