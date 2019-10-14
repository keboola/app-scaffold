<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

class OperationImportCollection
{
    /**
     * @var array|OperationImport[]
     */
    private $importedOperations = [];

    public function addImportedOperation(OperationImport $operationImport): void
    {
        foreach ($this->importedOperations as $operation) {
            if ($operation->getOperationId() === $operationImport->getOperationId()) {
                throw new \Exception(sprintf(
                    'Duplicite operation name. Operation "%s" already exists.',
                    $operation->getOperationId()
                ));
            }
        }
        $this->importedOperations[] = $operationImport;
    }

    /**
     * @return array|OperationImport[]
     */
    public function getImportedOperations(): array
    {
        return $this->importedOperations;
    }
}
