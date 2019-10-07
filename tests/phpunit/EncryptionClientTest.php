<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests;

use Keboola\ScaffoldApp\EncryptionClient;
use Keboola\StorageApi\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EncryptionClientTest extends TestCase
{
    public function testCreateForStorageApi(): void
    {
        /** @var Client|MockObject $sapiClientMock */
        $sapiClientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sapiClientMock->method('indexAction')
            ->willReturn([
                'services' => [
                    [
                        'id' => 'encryption',
                        'url' => 'https://url',
                    ],
                ],
            ]);
        $client = EncryptionClient::createForStorageApi($sapiClientMock);
        self::assertInstanceOf(EncryptionClient::class, $client);
    }
}
