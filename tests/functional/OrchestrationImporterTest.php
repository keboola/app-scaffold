<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\FunctionalTests;

use Keboola\ScaffoldApp\ApiClientStore;
use Keboola\ScaffoldApp\Importer\OrchestrationImporter;
use Keboola\ScaffoldApp\Operation\OperationsConfig;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldAction;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContextLoader;
use Keboola\Temp\Temp;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Output\NullOutput;

class OrchestrationImporterTest extends FunctionalBaseTestCase
{
    public function testImport(): void
    {
        $this->clearWorkspace();

        $scaffoldId = 'PassThroughTest';
        $inputParameters = [
            'snowflakeExtractor' => [
                'val2' => 'val',
            ],
            'connectionWriter' => [],
            'main' => [],
        ];

        $scaffoldFolder = __DIR__ . '/../phpunit/mock/scaffolds/' . $scaffoldId;
        $loader = new ExecutionContextLoader($inputParameters, $scaffoldFolder);

        $executionContext = $loader->getExecutionContext();

        $action = new UseScaffoldAction(
            $executionContext,
            new ApiClientStore(new NullLogger()),
            new NullLogger
        );
        $action->run();

        $orchestrationId = $executionContext->getOperationsQueue()->getFinishedOperationData('main');

        $temp = new Temp();
        $temp->initRunFolder();
        $tmpFolder = $temp->getTmpFolder();

        $importer = new OrchestrationImporter(
            $this->apiClients->getStorageApiClient(),
            $this->apiClients->getOrchestrationApiClient(),
            new NullOutput(),
            $tmpFolder
        );
        $importer->importOrchestration($orchestrationId, 'TestScaffold');

        self::assertFileExists($tmpFolder . '/TestScaffold/operations');
        self::assertFileExists($tmpFolder . '/TestScaffold/operations/' . OperationsConfig::CREATE_ORCHESTRATION);
        self::assertFileExists($tmpFolder . '/TestScaffold/operations/' . OperationsConfig::CREATE_CONFIGURATION_ROWS);
        self::assertFileExists($tmpFolder . '/TestScaffold/operations/' . OperationsConfig::CREATE_CONFIGURATION);
        self::assertFileExists($tmpFolder . '/TestScaffold/manifest.json');
        self::assertFileExists($tmpFolder . '/TestScaffold/ScaffoldDefinition.php');
    }
}
