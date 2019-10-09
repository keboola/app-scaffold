<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;

interface OperationInterface
{
    public function execute(OperationConfigInterface $operationConfig, FinishedOperationsStore $store): void;
}
