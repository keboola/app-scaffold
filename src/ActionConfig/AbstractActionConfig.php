<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\ActionConfig;

abstract class AbstractActionConfig implements ActionConfigInterface
{
    /** @var string|null */
    protected $id;

    /** @var string */
    protected $action;

    public function getAction(): string
    {
        return $this->action;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
