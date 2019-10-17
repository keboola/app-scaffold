<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Command;

use Keboola\ScaffoldApp\Command\ImportOrchestrationCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ImportOrchestrationCommandTest extends TestCase
{
    public function testExecuteArgumentValidation(): void
    {
        $command = $this->getCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'KBC_URL' => 'http://connection.keboola.com', // only https
            'SAPI_TOKEN' => 'xxxx',
            'ORCHESTRATION_ID' => '1324564x', // only numeric
            'SCAFFOLD_ID' => 'Ex1', // min 5 chars only alpha
        ]);

        $output = $commandTester->getDisplay();
        self::assertContains('The url "http://connection.keboola.com" is not a valid KBC url.', $output);
        self::assertContains('Orchestration id must be number.', $output);
        self::assertContains('Scaffold id must be at least 5 characters long.', $output);
        self::assertContains('Scaffold id is part of PHP namespace alpha characters are only allowed.', $output);
        self::assertEquals(1, $commandTester->getStatusCode());
    }

    private function getCommand(): Command
    {
        $application = new Application();

        $application->add(new ImportOrchestrationCommand());

        $command = $application->find('scaffold:import:orchestration');
        return $command;
    }
}
