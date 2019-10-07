<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer;

use Keboola\Component\JsonHelper;
use Keboola\Component\UserException;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\Orchestrator\OrchestrationTask;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class OrchestrationImporter
{
    private const NUMBER_OF_ADDITIONAL_OPERATIONS = 3;
    private const SCAFFOLD_DIRS = [
        'CreateConfiguration',
        'CreateConfigRows',
        'CreateOrchestration',
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
        OutputInterface $output
    ) {
        $this->storageApiClient = $storageApiClient;
        $this->orchestrationApiClient = $orchestrationApiClient;
        $this->output = $output;

        $this->componentsApiClient = new Components($this->storageApiClient);
        $this->scaffoldsDir = __DIR__ . '/../../scaffolds';
    }

    public function importOrchestration(
        int $orchestrationId,
        string $scaffoldName
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
        $this->prepareScaffoldFolderStructure($scaffoldName);
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

            $importedOperations[] = $this->importTask($scaffoldName, $task, $configurationId);

            $p->advance();
        }

        $p->setMessage('# Dumping CreateOrchestration operation file.');
        $this->dumpOrchestration($orchestration, $scaffoldName, $importedOperations);
        $p->advance();

        $p->setMessage('# Dumping ScaffoldDefinition template.');
        $this->dumpScaffoldDefinitionTemplate($scaffoldName);
        $p->advance();

        $p->finish();
    }

    private function prepareScaffoldFolderStructure(string $scaffoldName): void
    {
        $fs = new Filesystem();
        if ($fs->exists($this->scaffoldsDir . '/' . $scaffoldName)) {
            throw new UserException(sprintf(
                'Scaffold %s already exists.',
                $scaffoldName
            ));
        }

        // create folders
        foreach (self::SCAFFOLD_DIRS as $folder) {
            $fs->mkdir(sprintf(
                '%s/%s/operations/%s',
                $this->scaffoldsDir,
                $scaffoldName,
                $folder
            ));
        }
    }

    private function importTask(
        string $scaffoldName,
        OrchestrationTask $task,
        string $configurationId
    ): OperationImport {
        $configuration = $this->componentsApiClient->getConfiguration($task->getComponent(), $configurationId);

        $operationImport = new OperationImport(
            'configuration_' . $this->generateRandomSufix(),
            $task->getComponent(),
            [
                'name' => $configuration['name'],
                'configuration' => $configuration['configuration'],
            ],
            $task,
            $configuration['rows']
        );

        $this->dumpOperation($scaffoldName, $operationImport);

        return $operationImport;
    }

    private function generateRandomSufix(): string
    {
        return bin2hex(random_bytes(2));
    }

    private function dumpOperation(
        string $scaffoldName,
        OperationImport $import
    ): void {
        JsonHelper::writeFile(
            sprintf(
                '%s/%s/operations/CreateConfiguration/%s.json',
                $this->scaffoldsDir,
                $scaffoldName,
                $import->getOperationId()
            ),
            $import->getCreateConfigurationJsonArray(),
            true
        );

        if (0 === count($import->getConfigRows())) {
            return;
        }
        // dump ConfigRows
        JsonHelper::writeFile(
            sprintf(
                '%s/%s/operations/CreateConfigRows/%s.json',
                $this->scaffoldsDir,
                $scaffoldName,
                $import->getOperationId()
            ),
            $import->getConfigRowsJsonArray(),
            true
        );
    }

    /**
     * @param array $orchestration
     * @param string $scaffoldName
     * @param OperationImport[] $importedOperations
     */
    private function dumpOrchestration(
        array $orchestration,
        string $scaffoldName,
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
                '%s/%s/operations/CreateOrchestration/orchestration.%s.json',
                $this->scaffoldsDir,
                $scaffoldName,
                $this->generateRandomSufix()
            ),
            $orchestrationOperationConfig,
            true
        );
    }

    private function dumpScaffoldDefinitionTemplate(string $scaffoldName): void
    {
        $definition = <<<EOT
<?php

declare(strict_types=1);

namespace Keboola\Scaffolds\\$scaffoldName;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ScaffoldDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        \$parametersNode = parent::getParametersDefinition();
        // TODO: definition
        return \$parametersNode;
    }
}

EOT;
        $fs = new Filesystem();
        $fs->dumpFile(
            sprintf(
                '%s/%s/%s',
                $this->scaffoldsDir,
                $scaffoldName,
                'ScaffoldDefinition.php'
            ),
            $definition
        );
    }
}
