<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Exception;
use Keboola\ScaffoldApp\OperationConfig\CreateCofigurationRowsOperationConfig;
use Keboola\ScaffoldApp\OperationConfig\OperationConfigInterface;
use Keboola\StorageApi\Components;
use Keboola\StorageApi\Options\Components\Configuration;
use Psr\Log\LoggerInterface;

class CreateConfigurationRowsOperation implements OperationInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Components
     */
    private $componentsApiClient;

    public function __construct(
        Components $componentsApiClient,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->componentsApiClient = $componentsApiClient;
    }

    /**
     * @param CreateCofigurationRowsOperationConfig $operationConfig
     */
    public function execute(
        OperationConfigInterface $operationConfig,
        FinishedOperationsStore $store
    ): void {
        $this->logger->info(sprintf(
            'Creating config rows for operation "%s"',
            $operationConfig->getOperationReferenceId()
        ));

        $componentConfiguration = $store->getOperationData($operationConfig->getOperationReferenceId());

        if (!$componentConfiguration instanceof Configuration) {
            throw new Exception(sprintf(
                'Operation "%s" is required to be CreateConfiguration.',
                $operationConfig->getOperationReferenceId()
            ));
        }

        foreach ($operationConfig->getIterator($componentConfiguration) as $rowConfiguration) {
            $response = $this->componentsApiClient->addConfigurationRow($rowConfiguration);
            $rowConfiguration->setRowId($response['id']);
            $this->logger->info(sprintf('Row for %s created', $response['id']));

            $store->add(
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
