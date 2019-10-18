<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests;

use Keboola\Component\JsonHelper;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\ScaffoldApp\EncryptionClient;
use Keboola\ScaffoldApp\Operation\FinishedOperationsStore;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use ReflectionClass;
use Throwable;
use Keboola\ScaffoldApp\Component;
use PHPUnit\Framework\TestCase;

class ComponentTest extends TestCase
{
    public function testActionListScaffolds(): void
    {
        $reflection = new ReflectionClass(Component::class);

        $method = $reflection->getMethod('actionListScaffolds');

        $response = $method->invokeArgs($reflection->newInstanceWithoutConstructor(), []);
        foreach ($response as $scaffold) {
            self::assertArrayHasKey('id', $scaffold);
        }
    }

    public function testExecuteOperations(): void
    {
        /** @var Client|MockObject $sapiClientMock */
        $sapiClientMock = self::getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sapiClientMock->method('verifyToken')->willReturn(['owner' => ['id' => 'ownderId']]);

        $sapiClientMock->method('apiPost')
            ->willReturnCallback(function (): array {
                return ['id' => 'newConfigurationId'];
            });

        /** @var OrchestratorClient|MockObject $orchestratorApiMock */
        $orchestratorApiMock = self::createMock(OrchestratorClient::class);
        $orchestratorApiMock->method('createOrchestration')->willReturn(['id' => 'createdOrchestrationId']);

        /** @var EncryptionClient|MockObject $encyptionApiMock */
        $encyptionApiMock = self::createMock(EncryptionClient::class);
        $encyptionApiMock->method('encryptConfigurationData')
            ->willReturnCallback(function ($data) {
                return $data;
            });

        /** @var Components|MockObject $componentsApiMock */
        $componentsApiMock = $this->getMockBuilder(Components::class)
            ->disableOriginalConstructor()
            ->getMock();
        $componentsApiMock->method('addConfiguration')->willReturn(['id' => 'createdConfigurationId']);
        $componentsApiMock->expects($this->exactly(1))
            ->method('addConfigurationRow')
            ->will($this->onConsecutiveCalls(['id' => 'rowId0']));

        $componentRef = new ReflectionClass(Component::class);
        $executeOperationsRef = $componentRef->getMethod('executeOperations');
        $executeOperationsRef->setAccessible(true);

        /** @var FinishedOperationsStore $store */
        $store = $executeOperationsRef->invokeArgs(
            $componentRef->newInstanceWithoutConstructor(),
            [
                __DIR__ . '/mock/scaffolds/PassThroughTest',
                [ // parameters
                    'snowflakeExtractor' => [
                        'val2' => 'val',
                    ],
                ],
                $sapiClientMock,
                $orchestratorApiMock,
                $encyptionApiMock,
                $componentsApiMock,
                new NullLogger(),
            ]
        );

        $created = $store->getOperationData('connectionWriter');
        self::assertInstanceOf(Configuration::class, $created);
        self::assertEquals('createdConfigurationId', $created->getConfigurationId());
        $json = JsonHelper::readFile(
            __DIR__ . '/mock/scaffolds/PassThroughTest/operations/CreateConfiguration/connectionWriter.json'
        );
        self::assertSame(
            $json['payload']['configuration'],
            $created->getConfiguration()
        );

        $created = $store->getOperationData('snowflakeExtractor');
        self::assertInstanceOf(Configuration::class, $created);
        self::assertEquals('createdConfigurationId', $created->getConfigurationId());
        $json = JsonHelper::readFile(
            __DIR__ . '/mock/scaffolds/PassThroughTest/operations/CreateConfiguration/snowflakeExtractor.json'
        );
        $expectedConfiguration = array_merge_recursive(
            $json['payload']['configuration'],
            ['val2' => 'val']
        );
        self::assertSame(
            $expectedConfiguration,
            $created->getConfiguration()
        );

        $created = $store->getOperationData('row.connectionWriter.rowId0');

        self::assertEquals('createdOrchestrationId', $store->getOperationData('main'));
    }

    public function testGetScaffoldConfigurationNotExisting(): void
    {
        $reflection = new ReflectionClass(Component::class);

        $method = $reflection->getMethod('getScaffoldConfigurationFolder');
        $method->setAccessible(true);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Scaffold "NonExistingScaffold" does not exist.');
        $method->invokeArgs($reflection->newInstanceWithoutConstructor(), ['NonExistingScaffold']);
    }
}
