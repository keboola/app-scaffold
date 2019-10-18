<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

use Keboola\Component\JsonHelper;
use Keboola\Component\UserException;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\ScaffoldApp\Component;
use Keboola\ScaffoldApp\Operation\OperationsConfig;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class OrchestrationImporter
{
    private const NUMBER_OF_ADDITIONAL_OPERATIONS = 4;
    private const SCAFFOLD_DIRS = [
        OperationsConfig::CREATE_CONFIGURATION,
        OperationsConfig::CREATE_CONFIGURATION_ROWS,
        OperationsConfig::CREATE_ORCHESTREATION,
    ];
    public const SCAFFOLD_TABLE_TAG = 'bdm.scaffold.table.tag';

    /**
     * @var Client
     */
    private $storageApiClient;

    /**
     * @var OrchestratorClient
     */
    private $orchestrationApiClient;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Components
     */
    private $componentsApiClient;

    /**
     * @var string
     */
    private $scaffoldsDir;

    public function __construct(
        Client $storageApiClient,
        OrchestratorClient $orchestrationApiClient,
        OutputInterface $output,
        string $scaffoldsDir = Component::SCAFFOLDS_DIR
    ) {
        $this->storageApiClient = $storageApiClient;
        $this->orchestrationApiClient = $orchestrationApiClient;
        $this->output = $output;

        $this->componentsApiClient = new Components($this->storageApiClient);
        $this->scaffoldsDir = $scaffoldsDir;
    }

    public function importOrchestration(
        int $orchestrationId,
        string $scaffoldId
    ): void {
        $this->output->writeln(sprintf('# Looking for orchestration id %s', $orchestrationId));
        $orchestration = $this->orchestrationApiClient->getOrchestration($orchestrationId);

        // setup progressbar
        $p = new ProgressBar(
            $this->output,
            count($orchestration['tasks']) + self::NUMBER_OF_ADDITIONAL_OPERATIONS
        );
        $p->setFormat('# %current%/%max% ## %message%' . PHP_EOL);

        $this->output->writeln(sprintf('# Importing orchestration %s', $orchestration['name']));

        $p->setMessage('# Creating scaffold directory structure.');
        $this->prepareScaffoldFolderStructure($scaffoldId);
        $p->advance();

        $importedOperations = new OperationImportCollection();
        foreach ($orchestration['tasks'] as $rawTask) {
            $task = OrchestrationTaskFactory::createTaskFromResponse($rawTask);

            // check for component configuration id
            if (empty($task->getActionParameters()['config'])) {
                throw new UserException(sprintf(
                    'Task %s for component %s missing config id in action parameters.',
                    $task->getAction(),
                    $task->getComponent()
                ));
            }
            $configurationId = $task->getActionParameters()['config'];

            $p->setMessage(sprintf(
                '# Dumping task %s for component %s with configurationId %s',
                $task->getAction(),
                $task->getComponent(),
                $configurationId
            ));

            $configuration = $this->componentsApiClient->getConfiguration($task->getComponent(), $configurationId);

            $operationImport = OperationImportFactory::createOperationImport(
                $configuration,
                $task,
                $scaffoldId,
                $this->output
            );
            $this->dumpOperation($operationImport);
            $importedOperations->addImportedOperation($operationImport);

            $p->advance();
        }

        $p->setMessage('# Dumping CreateOrchestration operation file.');
        $this->dumpOrchestration($orchestration, $scaffoldId, $importedOperations);
        $p->advance();

        $p->setMessage('# Dumping ScaffoldDefinition template.');
        $this->dumpScaffoldDefinitionTemplate($scaffoldId);
        $p->advance();

        $p->setMessage('# Dumping Scaffold manifest file.');
        $this->dumpScaffoldManifestTemplate($scaffoldId, $importedOperations, $orchestration);
        $p->advance();

        $p->finish();
    }

    private function prepareScaffoldFolderStructure(string $scaffoldId): void
    {
        $fs = new Filesystem();
        if ($fs->exists($this->scaffoldsDir . '/' . $scaffoldId)) {
            throw new UserException(sprintf(
                'Scaffold %s already exists.',
                $scaffoldId
            ));
        }

        // create folders
        foreach (self::SCAFFOLD_DIRS as $folder) {
            $fs->mkdir(sprintf(
                '%s/%s/operations/%s',
                $this->scaffoldsDir,
                $scaffoldId,
                $folder
            ));
        }
    }

    private function dumpOperation(
        OperationImport $import
    ): void {
        $operationFileName = sprintf(
            '%s/%s/operations/%s/%s.json',
            $this->scaffoldsDir,
            $import->getScaffoldId(),
            OperationsConfig::CREATE_CONFIGURATION,
            $import->getOperationId()
        );

        JsonHelper::writeFile(
            $operationFileName,
            $import->getCreateConfigurationJsonArray(),
            true
        );

        if (count($import->getConfigurationRows()) === 0) {
            return;
        }
        // dump ConfigRows
        JsonHelper::writeFile(
            sprintf(
                '%s/%s/operations/%s/%s.json',
                $this->scaffoldsDir,
                $import->getScaffoldId(),
                OperationsConfig::CREATE_CONFIGURATION_ROWS,
                $import->getOperationId()
            ),
            $import->getConfigurationRowsJsonArray(),
            true
        );
    }

    private function dumpOrchestration(
        array $orchestration,
        string $scaffoldId,
        OperationImportCollection $importedOperations
    ): void {
        $orchestrationOperationConfig = [
            'payload' => [
                'name' => $orchestration['name'],
                'tasks' => [],
            ],
        ];
        foreach ($importedOperations->getImportedOperations() as $operation) {
            $orchestrationOperationConfig['payload']['tasks'][] = $operation->getOrchestrationTaskJsonArray();
        }

        JsonHelper::writeFile(
            sprintf(
                '%s/%s/operations/%s/%s.json',
                $this->scaffoldsDir,
                $scaffoldId,
                OperationsConfig::CREATE_ORCHESTREATION,
                $this->getOrchestrationOperationId($orchestration)
            ),
            $orchestrationOperationConfig,
            true
        );
    }

    private function getOrchestrationOperationId(array $orchestration): string
    {
        return CamelCaseConverterHelper::convertToCamelCase(
            'orchestration-' . $orchestration['name'],
            CamelCaseConverterHelper::STOP_WORDS_NO_UNDERSCORE
        );
    }

    private function dumpScaffoldDefinitionTemplate(string $scaffoldId): void
    {
        $definition = <<<EOT
<?php

declare(strict_types=1);

namespace Keboola\Scaffolds\\$scaffoldId;

use Keboola\ScaffoldApp\ScaffoldInputDefinitionInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ScaffoldDefinition implements ScaffoldInputDefinitionInterface
{
    public function addInputsDefinition(
        ArrayNodeDefinition \$node
    ): ArrayNodeDefinition {
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        \$node->ignoreExtraKeys(false)
            ->end();
        // @formatter:on
        return \$node;
    }
}

EOT;
        $fs = new Filesystem();
        $fs->dumpFile(
            sprintf(
                '%s/%s/%s',
                $this->scaffoldsDir,
                $scaffoldId,
                'ScaffoldDefinition.php'
            ),
            $definition
        );
    }

    private function dumpScaffoldManifestTemplate(
        string $scaffoldId,
        OperationImportCollection $importedOperations,
        array $orchestration
    ): void {
        $manifestTemplate = [
            'name' => '',
            'author' => 'Keboola',
            'description' => '',
            'inputs' => [],
        ];

        foreach ($importedOperations->getImportedOperations() as $operation) {
            $manifestTemplate['inputs'][] = [
                'id' => $operation->getOperationId(),
                'componentId' => $operation->getComponentId(),
                'name' => $operation->getPayload()['name'] ?? '',
                'required' => true,
                'schema' => null,
            ];
        }

        $manifestTemplate['inputs'][] = [
            'id' => $this->getOrchestrationOperationId($orchestration),
            'componentId' => 'orchestrator',
            'name' => $orchestration['name'],
            'required' => true,
            'schema' => null,
        ];

        JsonHelper::writeFile(
            sprintf(
                '%s/%s/manifest.json',
                $this->scaffoldsDir,
                $scaffoldId
            ),
            $manifestTemplate,
            true
        );
    }
}
