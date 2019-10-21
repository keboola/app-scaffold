<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation\UseScaffoldExecutionContext;

use Exception;
use Keboola\ScaffoldApp\Operation\CreateConfigurationOperation;
use Keboola\ScaffoldApp\Operation\CreateOrchestrationOperation;
use Keboola\StorageApi\Options\Components\Configuration;

class FinishedOperation
{
    /**
     * @var string
     */
    private $operationId;

    /**
     * @var string
     */
    private $operationClass;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var array
     */
    private $userActions;

    /**
     * @param mixed $data
     */
    public function __construct(
        string $operationId,
        string $operationClass,
        $data,
        array $userActions = []
    ) {
        $this->operationId = $operationId;
        $this->operationClass = $operationClass;
        $this->data = $data;
        $this->userActions = $userActions;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    public function getOperationId(): string
    {
        return $this->operationId;
    }

    public function isInResponse(): bool
    {
        return in_array($this->operationClass, [
            CreateConfigurationOperation::class,
            CreateOrchestrationOperation::class,
        ]);
    }

    public function toResponseArray(): array
    {
        $response = [
            'id' => $this->operationId,
            'userActions' => $this->userActions,
        ];

        switch ($this->operationClass) {
            case CreateConfigurationOperation::class:
                /** @var Configuration $data */
                $data = $this->data;
                $response['configurationId'] = $data->getConfigurationId();
                $response['componentId'] = $data->getComponentId();
                break;
            case CreateOrchestrationOperation::class:
                $response['configurationId'] = $this->data;
                $response['componentId'] = 'orchestrator';
                break;
            default:
                throw new Exception(sprintf(
                    'Operation class "%s" is not supported for response.',
                    $this->operationClass
                ));
        }

        return $response;
    }
}
