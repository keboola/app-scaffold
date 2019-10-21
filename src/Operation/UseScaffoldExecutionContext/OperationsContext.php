<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation\UseScaffoldExecutionContext;

class OperationsContext
{

    /**
     * @var array|string[]
     */
    private $requiredOperations;

    /**
     * @var array|string[]
     */
    private $operationsToExecute;

    /**
     * @param array|string[] $requiredOperations
     * @param array|string[] $operationsToExecute
     */
    public function __construct(
        array $requiredOperations,
        array $operationsToExecute
    ) {
        $this->requiredOperations = $requiredOperations;
        $this->operationsToExecute = $operationsToExecute;
    }

    /**
     * @return array|string[]
     */
    public function getRequiredOperations(): array
    {
        return $this->requiredOperations;
    }

    /**
     * @return array|string[]
     */
    public function getOperationsToExecute(): array
    {
        return $this->operationsToExecute;
    }
}
