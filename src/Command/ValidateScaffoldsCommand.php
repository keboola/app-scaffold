<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Command;

use Keboola\ScaffoldApp\Command\Validation\InvalidScaffoldException;
use Keboola\ScaffoldApp\Command\Validation\ManifestValidator;
use Keboola\ScaffoldApp\Component;
use Keboola\ScaffoldApp\Importer\Decorator\DecoratorInterface;
use Keboola\ScaffoldApp\Operation\OperationsConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Validator\ConstraintViolationInterface;

class ValidateScaffoldsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'scaffold:validate';

    protected function configure(): void
    {
        $this
            ->setDescription('Vallidate all scaffolds.');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Scaffold validator');

        $finder = new Finder();
        $scaffolds = $finder->in(Component::SCAFFOLDS_DIR)
            ->directories()->depth(0);

        $table = new Table($io);
        $table->setHeaders(['Scaffold', 'State']);
        /** @var SplFileInfo $scaffoldDir */
        foreach ($scaffolds as $scaffoldDir) {
            $this->validateManifest($scaffoldDir);
            $this->validateOperationsFiles($scaffoldDir);
            $table->addRow([$scaffoldDir->getFilename(), '✓']);
        }
        $table->render();
    }

    private function validateManifest(SplFileInfo $scaffoldDir): void
    {
        $violations = (new ManifestValidator($scaffoldDir))->validate();

        if (0 !== count($violations)) {
            $messages = '';
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                $messages .= sprintf('%s : %s', $violation->getPropertyPath(), $violation->getMessage());
                $messages .= PHP_EOL;
            }
            throw new InvalidScaffoldException($scaffoldDir, $messages);
        }
    }

    private function validateOperationsFiles(SplFileInfo $scaffoldDir): void
    {
        $fs = new Filesystem();
        foreach ([
                OperationsConfig::CREATE_ORCHESTREATION,
                OperationsConfig::CREATE_CONFIGURATION_ROWS,
                OperationsConfig::CREATE_CONFIGURATION,
            ] as $operationDir) {
            $operationDir = sprintf('%s/operations/%s', $scaffoldDir->getPathname(), $operationDir);
            if (!$fs->exists($operationDir)) {
                // skip non existing operations dirs
                continue;
            }
            foreach ((new Finder())->in($operationDir)->depth(0)->files() as $operationFile) {
                $this->validateOperationsFile($scaffoldDir, $operationFile);
            }
        }
    }

    private function validateOperationsFile(
        SplFileInfo $scaffoldDir,
        SplFileInfo $operationFile
    ): void {
        preg_match_all(
            '/' . DecoratorInterface::USER_ACTION_KEY_PREFIX . '/',
            $operationFile->getContents(),
            $matches,
            PREG_OFFSET_CAPTURE
        );

        if (count(current($matches)) !== 0) {
            throw new InvalidScaffoldException(
                $scaffoldDir,
                $this->getOperationFileErrorMessage($matches, $operationFile)
            );
        }
    }

    private function getOperationFileErrorMessage(
        array $matches,
        SplFileInfo $operationFile
    ): string {
        $fileContent = $operationFile->getContents();
        $message = sprintf('User action need at file "%s" on lines: ', $operationFile->getPathname());
        $message .= PHP_EOL;

        $lines = [];
        foreach (current($matches) as $match) {
            $lines[] = substr_count(mb_substr($fileContent, 0, $match[1]), PHP_EOL) + 1;
        }

        $message .= implode(', ', $lines);

        return $message;
    }
}
