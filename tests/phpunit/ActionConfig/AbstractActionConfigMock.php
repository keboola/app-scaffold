<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\ActionConfig;

use Keboola\ScaffoldApp\ActionConfig\AbstractActionConfig;
use Keboola\ScaffoldApp\ActionConfig\ActionConfigInterface;

class AbstractActionConfigMock extends AbstractActionConfig
{
    public static function create(array $actionConfig, ?array $parameters): ActionConfigInterface
    {
        return new self();
    }
}
