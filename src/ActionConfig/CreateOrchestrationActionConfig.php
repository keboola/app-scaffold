<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\ActionConfig;

use Exception;

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
        $config->action = $actionConfig['action'];

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

        if (array_key_exists('tasks', $config->payload)) {
            $config->tasks = $config->payload['tasks'];
        }

        return $config;
    }

    public function populateOrchestrationTasksWithConfigurationIds(array $createdConfigurations): void
    {
        // TODO: validate if tasks are set
        // TODO: validate if all tasks has config

        foreach ($this->tasks as &$task) {
            $task = array_merge(
                $task,
                [
                    'actionParameters' => [
                        'config' => $createdConfigurations[$task['id']],
                    ],
                ]
            );
        }
    }

    public function getOrchestrationName(): string
    {
        return $this->orchestrationName;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getTasks(): array
    {
        return $this->tasks;
    }
}
