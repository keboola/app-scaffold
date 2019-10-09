<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

use Keboola\Orchestrator\OrchestrationTask;

final class OrchestrationTaskFactory
{
    public static function createTaskFromResponse(array $task): OrchestrationTask
    {
        return (new OrchestrationTask())
            ->setComponent($task['component'])
            ->setAction($task['action'])
            ->setActionParameters($task['actionParameters'])
            ->setContinueOnFailure($task['continueOnFailure'])
            ->setActive($task['active'])
            ->setTimeoutMinutes($task['timeoutMinutes'])
            ->setPhase($task['phase']);
    }
}
