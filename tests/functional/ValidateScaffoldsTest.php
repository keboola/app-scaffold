<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\FunctionalTests;

use Keboola\Component\JsonHelper;
use Keboola\ScaffoldApp\Component;
use Keboola\ScaffoldApp\Operation\OperationsConfig;
use PHPUnit\Framework\TestCase;
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
        self::assertFileExists(sprintf('%s/%s', $scaffoldDir, OperationsConfig::CREATE_ORCHESTREATION));
        self::assertFileExists(sprintf('%s/%s', $scaffoldDir, OperationsConfig::CREATE_CONFIGURATION));
        self::assertFileExists(sprintf('%s/%s', $scaffoldDir, OperationsConfig::CREATE_CONFIGURATION_ROWS));
    }

    private function validateManifest(array $manifest): void
    {
        self::assertArrayHasKey('author', $manifest);
        self::assertArrayHasKey('description', $manifest);
        self::assertArrayHasKey('inputs', $manifest);
    }
}
