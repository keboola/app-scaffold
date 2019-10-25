<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContext;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;

interface OperationInterface
{
    public function execute(OperationConfigInterface $operationConfig, ExecutionContext $executionContext): void;
}
