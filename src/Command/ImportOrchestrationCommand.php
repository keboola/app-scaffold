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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

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
            ->addArgument('SCAFFOLD_ID', InputArgument::REQUIRED, 'Name of new scaffold');
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

        $scaffoldId = ucfirst($input->getArgument('SCAFFOLD_ID'));
        $validator = Validation::createValidator();
        $violations = $validator->validate($scaffoldId, [
            new Assert\Length([
                'min' => 5,
                'minMessage' => 'Scaffold id must be at least {{ limit }} characters long.',
            ]),
            new Assert\Type([
                'type' => 'alpha',
                'message' => 'Scaffold id is part of PHP namespace alpha characters are only allowed.',
            ]),
        ]);

        if (0 !== count($violations)) {
            // there are errors, now you can show them
            foreach ($violations as $violation) {
                $output->writeln($violation->getMessage());
            }
            return 1;
        }

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
            $scaffoldId
        );

        return 0;
    }
}
