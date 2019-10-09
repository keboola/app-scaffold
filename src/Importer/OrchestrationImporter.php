<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

use Keboola\Component\JsonHelper;
use Keboola\Component\UserException;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\Orchestrator\OrchestrationTask;
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
        string $scaffoldsDir = __DIR__ . '/../../scaffolds'
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

        $importedOperations = [];
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

            $importedOperations[] = $this->importTask($scaffoldId, $task, $configurationId);

            $p->advance();
        }

        $p->setMessage('# Dumping CreateOrchestration operation file.');
        $this->dumpOrchestration($orchestration, $scaffoldId, $importedOperations);
        $p->advance();

        $p->setMessage('# Dumping ScaffoldDefinition template.');
        $this->dumpScaffoldDefinitionTemplate($scaffoldId);
        $p->advance();

        $p->setMessage('# Dumping Scaffold manifest file.');
        $this->dumpScaffoldManifestTemplate($scaffoldId);
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

    private function importTask(
        string $scaffoldId,
        OrchestrationTask $task,
        string $configurationId
    ): OperationImport {
        $configuration = $this->componentsApiClient->getConfiguration($task->getComponent(), $configurationId);

        $operationId = $this->convertToCamelCase($task->getComponent() . '_' . $this->generateRandomSufix());
        $operationImport = new OperationImport(
            lcfirst($operationId),
            $task->getComponent(),
            [
                'name' => $configuration['name'],
                'configuration' => $configuration['configuration'],
            ],
            $task,
            $configuration['rows']
        );

        $this->dumpOperation($scaffoldId, $operationImport);

        return $operationImport;
    }

    private function convertToCamelCase(string $string): string
    {
        foreach (['-', '.'] as $delimiter) {
            $string = ucwords($string, $delimiter);
        }
        return str_replace(['-', '.'], '', $string);
    }

    private function generateRandomSufix(): string
    {
        return bin2hex(random_bytes(2));
    }

    private function dumpOperation(
        string $scaffoldId,
        OperationImport $import
    ): void {
        JsonHelper::writeFile(
            sprintf(
                '%s/%s/operations/%s/%s.json',
                $this->scaffoldsDir,
                $scaffoldId,
                OperationsConfig::CREATE_CONFIGURATION,
                $import->getOperationId()
            ),
            $import->getCreateConfigurationJsonArray(),
            true
        );

        if (0 === count($import->getConfigurationRows())) {
            return;
        }
        // dump ConfigRows
        JsonHelper::writeFile(
            sprintf(
                '%s/%s/operations/%s/%s.json',
                $this->scaffoldsDir,
                $scaffoldId,
                OperationsConfig::CREATE_CONFIGURATION_ROWS,
                $import->getOperationId()
            ),
            $import->getConfigurationRowsJsonArray(),
            true
        );
    }

    /**
     * @param array $orchestration
     * @param string $scaffoldId
     * @param OperationImport[] $importedOperations
     */
    private function dumpOrchestration(
        array $orchestration,
        string $scaffoldId,
        array $importedOperations
    ): void {
        $orchestrationOperationConfig = [
            'payload' => [
                'name' => $orchestration['name'],
                'tasks' => [],
            ],
        ];
        foreach ($importedOperations as $operation) {
            $orchestrationOperationConfig['payload']['tasks'][] = $operation->getOrchestrationTaskJsonArray();
        }

        JsonHelper::writeFile(
            sprintf(
                '%s/%s/operations/%s/orchestration_%s.json',
                $this->scaffoldsDir,
                $scaffoldId,
                OperationsConfig::CREATE_ORCHESTREATION,
                $this->generateRandomSufix()
            ),
            $orchestrationOperationConfig,
            true
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

    private function dumpScaffoldManifestTemplate(string $scaffoldId): void
    {
        $manifestTemplate = [
            'author' => 'Keboola',
            'description' => 'Sample Description',
            'inputs' => [],
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
