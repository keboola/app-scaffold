<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Command;

use Keboola\Component\Logger;
use Keboola\Component\UserException;
use Keboola\Orchestrator\Client as OrchestratorClient;
use Keboola\ScaffoldApp\Importer\OrchestrationImporter;
use Keboola\StorageApi\Client;
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
        $output->writeln([
            '',
            '#####################',
            '# Scaffold importer #',
            '#####################',
            '',
        ]);

        $logger = new Logger();

        $client = new Client(
            [
                'token' => $input->getArgument('SAPI_TOKEN'),
                'url' => $input->getArgument('KBC_URL'),
                'logger' => $logger,
            ]
        );
        $orchestrationApiClient = OrchestratorClient::factory([
            'url' => $this->getSyrupApiUrl($client),
            'token' => $input->getArgument('SAPI_TOKEN'),
        ]);

        $importer = new OrchestrationImporter($client, $orchestrationApiClient, $output);
        $importer->importOrchestration(
            (int) $input->getArgument('ORCHESTRATION_ID'),
            $input->getArgument('SCAFFOLD_NAME')
        );
    }

    private function getSyrupApiUrl(Client $sapiClient): string
    {
        $index = $sapiClient->indexAction();
        foreach ($index['services'] as $service) {
            if ($service['id'] === 'syrup') {
                return $service['url'] . '/orchestrator';
            }
        }
        $tokenData = $sapiClient->verifyToken();
        throw new UserException(sprintf('Syrup not found in %s region', $tokenData['owner']['region']));
    }
}
