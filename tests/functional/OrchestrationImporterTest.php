<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\FunctionalTests;

use Keboola\ScaffoldApp\Importer\OrchestrationImporter;
use Keboola\ScaffoldApp\Operation\OperationsConfig;
use Keboola\Temp\Temp;
use Symfony\Component\Console\Output\NullOutput;

class OrchestrationImporterTest extends FunctionalBaseTestCase
{
    public function testImport(): void
    {
        $this->clearWorkspace();
        $executionContext = $this->exportTestScaffold(
            'PassThroughTest',
            [
                'snowflakeExtractor' => [
                    'val2' => 'val',
                ],
                'connectionWriter' => [],
                'main' => [],
            ]
        );

        $orchestrationId = $executionContext->getFinishedOperationData('main');

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
