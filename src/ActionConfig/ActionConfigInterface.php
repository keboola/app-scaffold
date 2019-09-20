<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\ActionConfig;

interface ActionConfigInterface
{
    public static function create(array $actionConfig, ?array $parameters): ActionConfigInterface;
}
