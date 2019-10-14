<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\FunctionalTests;

use Keboola\Component\JsonHelper;
use Keboola\ScaffoldApp\Component;
use Keboola\ScaffoldApp\Importer\Decorator\DecoratorInterface;
use Keboola\ScaffoldApp\Operation\OperationsConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * This is more like test scope validator for scaffolds.
 * Purpose is to fail when newly created or update scaffold is invalid
 */
class ValidateScaffoldsTest extends TestCase
{
    public function testScaffolds(): void
    {
        $finder = new Finder();
        $scaffolds = $finder->in(Component::SCAFFOLDS_DIR)
            ->directories()->depth(0);

        /** @var SplFileInfo $scaffoldDir */
        foreach ($scaffolds as $scaffoldDir) {
            $this->validateScaffold($scaffoldDir->getPathname());
        }
    }

    private function validateScaffold(string $scaffoldDir): void
    {
        $manifest = JsonHelper::readFile(sprintf('%s/manifest.json', $scaffoldDir));
        $this->validateManifest($manifest);
        $this->validateOperationsFiles($scaffoldDir);
    }

    private function validateManifest(array $manifest): void
    {
        self::assertArrayHasKey('author', $manifest);
        self::assertArrayHasKey('description', $manifest);
        self::assertArrayHasKey('inputs', $manifest);
    }

    private function validateOperationsFiles(string $scaffoldDir): void
    {
        $fs = new Filesystem();
        foreach ([
                     OperationsConfig::CREATE_ORCHESTREATION,
                     OperationsConfig::CREATE_CONFIGURATION_ROWS,
                     OperationsConfig::CREATE_CONFIGURATION,
                 ] as $operationDir) {
            $operationDir = sprintf('%s/operations/%s', $scaffoldDir, $operationDir);
            if (!$fs->exists($operationDir)) {
                // skip non existing operations dirs
                continue;
            }
            foreach ((new Finder())->in($operationDir)->depth(0)->files() as $operationFile) {
                $this->validateOperationsFile($operationFile);
            }
        }
    }

    private function validateOperationsFile(SplFileInfo $operationFile): void
    {
        preg_match_all(
            '/' . DecoratorInterface::USER_ACTION_KEY_PREFIX . '/',
            $operationFile->getContents(),
            $matches,
            PREG_OFFSET_CAPTURE
        );
        self::assertCount(0, current($matches), $this->getOperationFileErrorMessage($matches, $operationFile));
    }

    private function getOperationFileErrorMessage(
        array $matches,
        SplFileInfo $operationFile
    ): string {
        $fileContent = $operationFile->getContents();
        $message = sprintf('User action need at file "%s" on lines: ', $operationFile->getPathname());

        $lines = [];
        foreach (current($matches) as $match) {
            $lines[] = substr_count(mb_substr($fileContent, 0, $match[1]), PHP_EOL) + 1;
        }

        $message .= implode(', ', $lines);

        return $message;
    }
}
