<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation;

use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\CreateConfigurationRowsOperation;
use Keboola\ScaffoldApp\Operation\CreateOrchestrationOperation;
use Keboola\ScaffoldApp\Operation\FinishedOperation;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\TestCase;

class FinishedOperationTest extends TestCase
{
    public function testGetData(): void
    {
        $finishedOperation = $this->getInstance(['sample' => 'value'], CreateConfigurationOperation::class);
        self::assertSame(['sample' => 'value'], $finishedOperation->getData());
    }

    /**
     * @param mixed $data
     */
    private function getInstance(
        $data,
        string $operationClass
    ): FinishedOperation {
        return new FinishedOperation('operationId', $operationClass, $data, ['action1']);
    }

    public function testGetOperationId(): void
    {
        $finishedOperation = $this->getInstance(null, CreateConfigurationOperation::class);
        self::assertEquals('operationId', $finishedOperation->getOperationId());
    }

    public function testIsInResponse(): void
    {
        $finishedOperation = $this->getInstance(null, CreateConfigurationOperation::class);
        self::assertTrue($finishedOperation->isInResponse());

        $finishedOperation = $this->getInstance(null, CreateOrchestrationOperation::class);
        self::assertTrue($finishedOperation->isInResponse());

        $finishedOperation = $this->getInstance(null, CreateConfigurationRowsOperation::class);
        self::assertFalse($finishedOperation->isInResponse());
    }

    public function testToResponseArrayCreateConfigurationOperation(): void
    {
        $data = new Configuration();
        $data->setComponentId('keboola.component.id');
        $data->setConfigurationId(111);
        $finishedOperation = $this->getInstance($data, CreateConfigurationOperation::class);
        self::assertSame([
            'id' => 'operationId',
            'userActions' => [
                'action1',
            ],
            'configurationId' => 111,
            'componentId' => 'keboola.component.id',
        ], $finishedOperation->toResponseArray());
    }

    public function testToResponseArrayCreateConfigurationRowsOperation(): void
    {
        $finishedOperation = $this->getInstance(null, CreateConfigurationRowsOperation::class);
        self::expectException(\Throwable::class);
        self::expectExceptionMessage(
            'Operation class "Keboola\ScaffoldApp\Operation\CreateConfigurationRowsOperation"'
            . ' is not supported for response.'
        );
        $finishedOperation->toResponseArray();
    }

    public function testToResponseArrayCreateOrchestrationOperation(): void
    {
        $finishedOperation = $this->getInstance(111, CreateOrchestrationOperation::class);
        self::assertSame([
            'id' => 'operationId',
            'userActions' => [
                'action1',
            ],
            'configurationId' => 111,
            'componentId' => 'orchestrator',
        ], $finishedOperation->toResponseArray());
    }
}
