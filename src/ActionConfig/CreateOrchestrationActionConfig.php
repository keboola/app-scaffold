<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\ActionConfig;

use Exception;
use Keboola\StorageApi\Options\Components\Configuration;

class CreateOrchestrationActionConfig extends AbstractActionConfig
{
    /** @var array */
    private $payload = [];

    /** @var array */
    private $tasks = [];

    /** @var string */
    private $orchestrationName;

    /**
     * @return CreateOrchestrationActionConfig
     */
    public static function create(array $actionConfig, ?array $parameters): ActionConfigInterface
    {
        $config = new self();

        if (array_key_exists('id', $actionConfig)) {
            $config->id = $actionConfig['id'];
        }

        if (array_key_exists('payload', $actionConfig)) {
            $config->payload = $actionConfig['payload'];
        } else {
            throw new Exception('Actions create.orchestration missing payload');
        }

        if (array_key_exists('name', $config->payload)) {
            $config->orchestrationName = $config->payload['name'];
        } else {
            throw new Exception('Actions create.orchestration missing name');
        }

        if (array_key_exists('tasks', $config->payload) && 0 !== count($config->payload['tasks'])) {
            $config->tasks = $config->payload['tasks'];
        } else {
            throw new Exception('Actions create.orchestration tasks are empty');
        }

        return $config;
    }

    /**
     * @param array<Configuration> $createdConfigurations
     */
    public function populateOrchestrationTasksWithConfigurationIds(array $createdConfigurations): void
    {
        foreach ($this->tasks as &$task) {
            /** @var Configuration $componentConfiguration */
            $componentConfiguration = $createdConfigurations[$task['refConfigId']];
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

    public function getOrchestrationName(): string
    {
        return $this->orchestrationName;
    }

    public function getTasks(): array
    {
        return $this->tasks;
    }
}
