<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\OperationConfig;

use Exception;
use Keboola\StorageApi\Options\Components\Configuration;

class CreateCofigRowOperationConfig implements OperationConfigInterface
{
    /** @var string */
    private $refConfigId;

    /** @var array */
    private $rows = [];

    /**
     * @return CreateCofigRowOperationConfig
     */
    public static function create(array $actionConfig, array $parameters): OperationConfigInterface
    {
        $config = new self();

        if (empty($actionConfig['refConfigId'])) {
            throw new Exception('Operation create.configrows missing refConfigId');
        }
        $config->refConfigId = $actionConfig['refConfigId'];

        if (empty($actionConfig['rows'])) {
            throw new Exception('Operation create.configrows has no rows');
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
