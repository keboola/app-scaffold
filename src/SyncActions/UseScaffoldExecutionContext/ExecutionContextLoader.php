<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext;

use Keboola\Component\JsonHelper;
use Keboola\ScaffoldApp\Operation\OperationsConfig;
use Keboola\ScaffoldApp\Operation\OperationsQueue;
use Symfony\Component\Finder\Finder;

class ExecutionContextLoader
{
    /**
     * @var array
     */
    private $scaffoldInputs;

    /**
     * @var string
     */
    private $scaffoldFolder;

    public function __construct(array $scaffoldInputs, string $scaffoldFolder)
    {
        $this->scaffoldInputs = $scaffoldInputs;
        $this->scaffoldFolder = $scaffoldFolder;
    }

    public function getExecutionContext(): ExecutionContext
    {
        $manifest = JsonHelper::readFile(sprintf('%s/manifest.json', $this->scaffoldFolder));

        $operationsContext = $this->loadOperations($manifest);
        OperationsContextValidator::validate($operationsContext);
        $operationsQueue = $this->getOperationQueue($operationsContext);

        $executionContext = new ExecutionContext(
            $manifest,
            $this->scaffoldInputs,
            $this->scaffoldFolder,
            $operationsQueue
        );

        ExecutionContextValidator::validateContext($executionContext, $operationsContext);

        return $executionContext;
    }

    private function loadOperations(array $manifest): OperationsContext
    {
        $requiredOperations = [];
        $toExecuteOperations = [];
        foreach ($manifest['inputs'] as $operationInput) {
            if ($operationInput['required'] === true) {
                $requiredOperations[] = $operationInput['id'];
            }
        }

        foreach ($this->scaffoldInputs as $operationId => $input) {
            $toExecuteOperations[] = $operationId;
        }

        return new OperationsContext($requiredOperations, $toExecuteOperations);
    }

    private function getOperationQueue(
        OperationsContext $context
    ): OperationsQueue {
        $operationQueue = new OperationsQueue();
        foreach ([
                     // order is important
                     OperationsConfig::CREATE_CONFIGURATION,
                     OperationsConfig::CREATE_CONFIGURATION_ROWS,
                     OperationsConfig::CREATE_ORCHESTREATION,
                 ] as $operationsName
        ) {
            $operationsFileNames = array_map(function ($operation) {
                return $operation . '.json';
            }, $context->getOperationsToExecute());

            $finder = new Finder();
            $foundOperations = $finder->in(sprintf('%s/operations/%s/', $this->scaffoldFolder, $operationsName))
                ->files()
                ->name($operationsFileNames) // include only operations that will be executed
                ->depth(0);

            foreach ($foundOperations->getIterator() as $operationFile) {
                $operationQueue->addOperation($operationsName, $operationFile);
            }
        }

        return $operationQueue;
    }
}
