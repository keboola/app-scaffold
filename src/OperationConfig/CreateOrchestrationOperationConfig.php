<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\OperationConfig;

use Exception;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContext;
use Keboola\StorageApi\Options\Components\Configuration;

class CreateOrchestrationOperationConfig implements OperationConfigInterface
{
    /** @var string */
    protected $id;

    /** @var array */
    private $payload = [];

    /** @var array */
    private $tasks = [];

    /** @var string */
    private $orchestrationName;

    /** @var string */
    private $description;

    /**
     * @return CreateOrchestrationOperationConfig
     */
    public static function create(
        string $operationId,
        array $operationConfig,
        array $parameters
    ): OperationConfigInterface {
        $config = new self();

        $config->id = $operationId;

        if (empty($operationConfig['payload'])) {
            throw new Exception('Operation create.orchestration missing payload');
        }
        $config->payload = $operationConfig['payload'];

        if (empty($config->payload['name'])) {
            throw new Exception('Operation create.orchestration missing name');
        }
        $config->orchestrationName = $config->payload['name'];

        if (empty($config->payload['tasks'])) {
            throw new Exception('Operation create.orchestration tasks are empty');
        }
        $config->tasks = $config->payload['tasks'];

        if (!empty($config->payload['description'])) {
            $config->description = $config->payload['description'];
        } else {
            $config->description = '';
        }
        return $config;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOrchestrationName(): string
    {
        return $this->orchestrationName;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPayload(): array
    {
        $payload = $this->payload;
        $payload['tasks'] = $this->tasks;
        return $payload;
    }

    public function populateOrchestrationTasksWithConfigurationIds(
        ExecutionContext $executionContext
    ): void {
        foreach ($this->tasks as &$task) {
            /** @var Configuration $componentConfiguration */
            $componentConfiguration = $executionContext->getOperationsQueue()
                ->getFinishedOperationData($task['operationReferenceId']);
            $task = array_merge(
                $task,
                [
                    'actionParameters' => [
                        'config' => $componentConfiguration->getConfigurationId(),
                    ],
                ]
            );
        }
    }
}
