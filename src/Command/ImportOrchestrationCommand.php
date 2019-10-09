<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Command;

use Keboola\Component\Logger;
use Keboola\ScaffoldApp\Importer\OrchestrationImporter;
use Keboola\ScaffoldApp\OrchestratorClientFactory;
use Keboola\StorageApi\Client;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportOrchestrationCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'scaffold:import:orchestration';

    protected function configure(): void
    {
        $this
            ->setDescription('Import scaffold from orchestration.');

        $this
            ->addArgument('KBC_URL', InputArgument::REQUIRED, 'Keboola connection storage api url')
            ->addArgument('SAPI_TOKEN', InputArgument::REQUIRED, 'Storage API token')
            ->addArgument('ORCHESTRATION_ID', InputArgument::REQUIRED, 'ID of orchestration configuration to import')
            ->addArgument('SCAFFOLD_NAME', InputArgument::REQUIRED, 'Name of new scaffold');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(<<<EOT
____ ____ ____ ____ ____ ____ ____ ____
||S |||c |||a |||f |||f |||o |||l |||d ||
||__|||__|||__|||__|||__|||__|||__|||__||
|/__\|/__\|/__\|/__\|/__\|/__\|/__\|/__\|
 ____ ____ ____ ____ ____ ____ ____ ____
||i |||m |||p |||o |||r |||t |||e |||r ||
||__|||__|||__|||__|||__|||__|||__|||__||
|/__\|/__\|/__\|/__\|/__\|/__\|/__\|/__\|

EOT
        );

        $logger = $output->isVerbose() ? new Logger() : new NullLogger();

        $client = new Client(
            [
                'token' => $input->getArgument('SAPI_TOKEN'),
                'url' => $input->getArgument('KBC_URL'),
                'logger' => $logger,
            ]
        );
        $orchestrationApiClient = OrchestratorClientFactory::createForStorageApi($client);

        $importer = new OrchestrationImporter($client, $orchestrationApiClient, $output);
        $importer->importOrchestration(
            (int) $input->getArgument('ORCHESTRATION_ID'),
            $input->getArgument('SCAFFOLD_NAME')
        );
    }

}
