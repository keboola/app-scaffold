<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\OperationConfig;

interface OperationConfigInterface
{
    public static function create(array $actionConfig, array $parameters): OperationConfigInterface;
}
