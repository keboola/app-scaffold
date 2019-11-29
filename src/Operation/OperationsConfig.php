<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

final class OperationsConfig
{
    public const CREATE_CONFIGURATION = 'CreateConfiguration';
    public const CREATE_CONFIGURATION_ROWS = 'CreateConfigurationRows';
    public const CREATE_ORCHESTRATION = 'CreateOrchestration';

    public const ALL_OPERATIONS_CONFIGS = [
        self::CREATE_CONFIGURATION,
        self::CREATE_CONFIGURATION_ROWS,
        self::CREATE_ORCHESTRATION,
    ];
}
