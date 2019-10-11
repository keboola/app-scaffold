<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

use Keboola\Orchestrator\OrchestrationTask;

class OperationImport
{
    /**
     * @var string
     */
    private $operationId;

    /**
     * @var string
     */
    private $componentId;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var OrchestrationTask
     */
    private $task;

    /**
     * @var array
     */
    private $configurationRows;

    public function __construct(
        string $operationId,
        string $componentId,
        array $payload,
        OrchestrationTask $task,
        array $configurationRows = []
    ) {
        $this->operationId = $operationId;
        $this->componentId = $componentId;
        $this->payload = $payload;
        $this->task = $task;
        $this->configurationRows = $configurationRows;
    }

    public function getComponentId(): string
    {
        return $this->componentId;
    }

    public function getConfigurationRows(): array
    {
        return $this->configurationRows;
    }

    public function getConfigurationRowsJsonArray(): array
    {
        // will strip all stuff from response
        $output = [];
        foreach ($this->configurationRows as $singleRow) {
            $output[] = [
                'configuration' => $singleRow['configuration'],
                'description' => $singleRow['description'],
                'name' => $singleRow['name'],
            ];
        }

        return $output;
    }

    public function getCreateConfigurationJsonArray(): array
    {
        return [
            'componentId' => $this->componentId,
            'payload' => $this->payload,
        ];
    }

    public function getOperationId(): string
    {
        return $this->operationId;
    }

    public function getOrchestrationTaskJsonArray(): array
    {
        return [
            'component' => $this->componentId,
            'operationReferenceId' => $this->operationId,
            'action' => $this->task->getAction(),
            'timeoutMinutes' => $this->task->getTimeoutMinutes(),
            'active' => $this->task->getActive(),
            'continueOnFailure' => $this->task->getContinueOnFailure(),
            'phase' => $this->task->getPhase(),
        ];
    }
}
