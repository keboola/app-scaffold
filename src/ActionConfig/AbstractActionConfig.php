<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\ActionConfig;

abstract class AbstractActionConfig implements ActionConfigInterface
{
    /** @var string|null */
    protected $id;

    public function getId(): ?string
    {
        return $this->id;
    }
}
