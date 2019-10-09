<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\OperationConfig;

use Iterator;
use Keboola\StorageApi\Options\Components\Configuration;
use Keboola\StorageApi\Options\Components\ConfigurationRow;

class ConfigurationRowsIterator implements Iterator
{
    /** @var int */
    private $position = 0;

    /** @var array */
    private $rows = [];

    /** @var Configuration */
    private $componentConfiguration;

    public function __construct(
        array $rows,
        Configuration $componentConfiguration
    ) {
        $this->rows = $rows;
        $this->componentConfiguration = $componentConfiguration;
    }

    private function getConfigrationForRow(int $index): ConfigurationRow
    {
        $row = $this->rows[$index];
        $rowConfiguration = new ConfigurationRow($this->componentConfiguration);
        $rowConfiguration->setName($row['name']);
        $rowConfiguration->setConfiguration($row['configuration']);

        return $rowConfiguration;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): ConfigurationRow
    {
        return $this->getConfigrationForRow($this->position);
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->rows[$this->position]);
    }
}
