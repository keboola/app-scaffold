<?php

declare(strict_types=1);

namespace MyComponent;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getScaffoldName(): string
    {
        //only one scaffold can be passed in parameters
        return array_keys($this->getParameters())[0];
    }

    public function getScaffoldParameters(): array
    {
        //only one scaffold can be passed in parameters
        return $this->getParameters()[$this->getScaffoldName()];
    }
}
