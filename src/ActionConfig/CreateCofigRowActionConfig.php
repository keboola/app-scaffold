<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\ActionConfig;

use Exception;
use Keboola\StorageApi\Options\Components\Configuration;

class CreateCofigRowActionConfig extends AbstractActionConfig
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

        if (array_key_exists('id', $actionConfig)) {
            $config->id = $actionConfig['id'];
        }
        if (array_key_exists('refConfigId', $actionConfig)) {
            $config->refConfigId = $actionConfig['refConfigId'];
        } else {
            throw new Exception('Actions create.configrows missing refConfigId');
        }
        if (array_key_exists('rows', $actionConfig) && 0 !== count($actionConfig['rows'])) {
            $config->rows = $actionConfig['rows'];
        } else {
            throw new Exception('Actions create.configrows has no rows');
        }

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
