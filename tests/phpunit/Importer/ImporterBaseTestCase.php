<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Importer;

use Keboola\Orchestrator\OrchestrationTask;
use Keboola\ScaffoldApp\Importer\OrchestrationTaskFactory;
use PHPUnit\Framework\TestCase;

class ImporterBaseTestCase extends TestCase
{
    public function getExampleOrchstrationTask(): OrchestrationTask
    {
        return OrchestrationTaskFactory::createTaskFromResponse([
            'component' => 'keboola.component',
            'action' => 'run',
            'actionParameters' => [
            ],
            'continueOnFailure' => false,
            'active' => true,
            'timeoutMinutes' => null,
            'phase' => 1,
        ]);
    }
}
