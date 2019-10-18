<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Exception;
use Keboola\ScaffoldApp\OperationConfig\CreateCofigurationRowsOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;
use Keboola\StorageApi\Options\Components\Configuration;

class CreateConfigurationRowsOperation implements OperationInterface
{
    /**
     * @param CreateCofigurationRowsOperationConfig $operationConfig
     */
    public function execute(
        OperationConfigInterface $operationConfig,
        ExecutionContext $executionContext
    ): void {
        $executionContext->getLogger()->info(sprintf(
            'Creating config rows for operation "%s"',
            $operationConfig->getOperationReferenceId()
        ));

        $componentConfiguration = $executionContext->getFinishedOperationData(
            $operationConfig->getOperationReferenceId()
        );

        if (!$componentConfiguration instanceof Configuration) {
            throw new Exception(sprintf(
                'Operation "%s" is required to be CreateConfiguration.',
                $operationConfig->getOperationReferenceId()
            ));
        }

        foreach ($operationConfig->getIterator($componentConfiguration) as $rowConfiguration) {
            $response = $executionContext->getComponentsApiClient()->addConfigurationRow($rowConfiguration);
            $rowConfiguration->setRowId($response['id']);
            $executionContext->getLogger()->info(sprintf('Row for %s created', $response['id']));

            $executionContext->finishOperation(
                sprintf(
                    'row.%s.%s',
                    $operationConfig->getOperationReferenceId(),
                    $rowConfiguration->getRowId()
                ),
                self::class,
                $rowConfiguration
            );
        }
    }
}
