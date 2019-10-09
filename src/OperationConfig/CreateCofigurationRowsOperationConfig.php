<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\OperationConfig;

use Exception;
use Keboola\StorageApi\Options\Components\Configuration;

class CreateCofigurationRowsOperationConfig implements OperationConfigInterface
{
    /** @var string */
    private $operationReferenceId;

    /** @var array */
    private $rows = [];

    /**
     * @return CreateCofigurationRowsOperationConfig
     */
    public static function create(
        string $operationId,
        array $configRows,
        array $parameters
    ): OperationConfigInterface {
        $config = new self();

        $config->operationReferenceId = $operationId;

        if (empty($configRows)) {
            throw new Exception('Operation create.configrows has no rows');
        }
        $config->rows = $configRows;

        return $config;
    }

    public function getIterator(
        Configuration $componentConfiguration
    ): ConfigRowIterator {
        return new ConfigRowIterator($this->rows, $componentConfiguration);
    }

    public function getOperationReferenceId(): string
    {
        return $this->operationReferenceId;
    }
}
