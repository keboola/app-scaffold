<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getScaffold(): array
    {
        return $this->getParameters()['scaffolds'][0];
    }

    public function getScaffoldName(): string
    {
        //only one scaffold can be passed in parameters
        return $this->getScaffold()['name'];
    }

    public function getScaffoldParameters(): array
    {
        //only one scaffold can be passed in parameters
        return $this->getScaffold()['parameters'];
    }
}
