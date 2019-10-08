<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\OperationConfig;

interface OperationConfigInterface
{
    /**
     * @param string $operationId - string operation id, this id is also name of json file
     * @param array $operationConfig - array with operation configuration
     * @param array $parameters - paramaters from user input
     * @return OperationConfigInterface
     */
    public static function create(
        string $operationId,
        array $operationConfig,
        array $parameters
    ): OperationConfigInterface;
}
