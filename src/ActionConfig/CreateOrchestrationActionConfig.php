<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\ActionConfig;

use Exception;
use Keboola\StorageApi\Options\Components\Configuration;

class CreateOrchestrationActionConfig implements ActionConfigInterface
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

        if (empty($actionConfig['payload'])) {
            throw new Exception('Actions create.orchestration missing payload');
        }
        $config->payload = $actionConfig['payload'];

        if (empty($config->payload['name'])) {
            throw new Exception('Actions create.orchestration missing name');
        }
        $config->orchestrationName = $config->payload['name'];

        if (empty($config->payload['tasks'])) {
            throw new Exception('Actions create.orchestration tasks are empty');
        }
        $config->tasks = $config->payload['tasks'];

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

    public function getPayload(): array
    {
        $payload = $this->payload;
        $payload['tasks'] = $this->tasks;
        return $payload;
    }
}
