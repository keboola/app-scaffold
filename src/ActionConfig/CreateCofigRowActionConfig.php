<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\ActionConfig;

use Exception;
use Keboola\StorageApi\Options\Components\Configuration;

class CreateCofigRowActionConfig implements ActionConfigInterface
{
    /** @var string */
    private $refConfigId;

    /** @var array */
    private $rows = [];

    /**
     * @return CreateCofigRowActionConfig
     */
    public static function create(array $actionConfig, ?array $parameters): ActionConfigInterface
    {
        $config = new self();

        if (empty($actionConfig['refConfigId'])) {
            throw new Exception('Actions create.configrows missing refConfigId');
        }
        $config->refConfigId = $actionConfig['refConfigId'];

        if (empty($actionConfig['rows'])) {
            throw new Exception('Actions create.configrows has no rows');
        }
        $config->rows = $actionConfig['rows'];

        return $config;
    }

    public function getRefConfigId(): string
    {
        return $this->refConfigId;
    }

    public function getIterator(Configuration $componentConfiguration): ConfigRowIterator
    {
        return new ConfigRowIterator($this->rows, $componentConfiguration);
    }
}
