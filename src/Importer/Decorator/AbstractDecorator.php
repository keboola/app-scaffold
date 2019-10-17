<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer\Decorator;

use Keboola\ScaffoldApp\Importer\OperationImport;
use Keboola\ScaffoldApp\Importer\TableNameConverter;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDecorator implements DecoratorInterface
{
    /**
     * @var TableNameConverter
     */
    protected $tableNameConverter;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function convertTableName(
        OperationImport $operationImport,
        string $tableName
    ): string {
        return $this->getTableNameConverter()->convertTableName($operationImport, $tableName);
    }

    private function getTableNameConverter(): TableNameConverter
    {
        if ($this->tableNameConverter === null) {
            $this->tableNameConverter = new TableNameConverter;
        }

        return $this->tableNameConverter;
    }

    public function convertTableNameToMetadataValue(
        OperationImport $operationImport,
        string $tableName
    ): string {
        return $this->getTableNameConverter()->convertTableNameToMetadataValue($operationImport, $tableName);
    }
}
