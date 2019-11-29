<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\SyncActions\UseScaffoldExecutionContext;

use Keboola\Component\JsonHelper;
use Keboola\Component\UserException;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContextLoader;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\RequirementsValidator;
use PHPUnit\Framework\TestCase;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContext;

class RequirementsValidatorTest extends TestCase
{
    public function testValidateRequirements(): void
    {
        $inputs = [
            'connectionWriter' => [
                'parameters' => [
                    '#token' => 'dummy',
                ],
            ],
            'main' => [
                'parameters' => [
                    'values' => null,
                ],
            ],
        ];
        $loader = new ExecutionContextLoader($inputs, __DIR__ . '/../../mock/scaffolds/WithRequirementsTest');
        $manifestFilePath = __DIR__ . '/../../mock/scaffolds/WithRequirementsTest/manifest.json';
        $manifest = JsonHelper::readFile($manifestFilePath);

        RequirementsValidator::validateRequirements(
            $manifest['requirements'],
            $loader->getExecutionContext()
        );

        $this->assertNotEmpty($manifest['requirements']);
        $this->assertSame($loader->getExecutionContext()->getManifestRequirements(), $manifest['requirements']);
        $this->assertNotEmpty($loader->getExecutionContext()->getManifestRequirements());
    }

    public function testInvalidRequirements(): void
    {
        $inputs = [
            'connectionWriter' => [
                'parameters' => [
                    '#token' => 'dummy',
                ],
            ],
            'main' => [
                'parameters' => [
                    'values' => null,
                ],
            ],
        ];
        $loader = new ExecutionContextLoader($inputs, __DIR__ . '/../../mock/scaffolds/WithRequirementsTest');
        $this->expectException(UserException::class);
        $this->expectExceptionMessage(sprintf(
            'The scaffold \'%s\' needs following requirements \'%s\'',
            $loader->getExecutionContext()->getScaffoldId(),
            implode(', ', $loader->getExecutionContext()->getManifestRequirements())
        ));

        $this->assertNotEmpty($loader->getExecutionContext()->getManifestRequirements());

        RequirementsValidator::validateRequirements(
            [],
            $loader->getExecutionContext()
        );
    }

    public function testValidateOutputs(): void
    {
        $inputs = [
            'connectionExtractor' => [
                'parameters' => [
                    '#token' => 'xxxxx',
                ],
            ],
            'transformation' => [
                'parameters' => [
                    'values' => null,
                ],
            ],
            'main' => [
                'parameters' => [
                    'values' => null,
                ],
            ],
        ];
        $loader = new ExecutionContextLoader($inputs, __DIR__ . '/../../mock/scaffolds/WithOutputsTest');

        RequirementsValidator::validateOutputs(
            ['test.TestingOutputs'],
            $loader->getExecutionContext()
        );

        $this->assertNotSame($loader->getExecutionContext()->getManifestOutputs(), ['test.TestingOutputs']);
        $this->assertNotEmpty($loader->getExecutionContext()->getManifestOutputs());
    }

    public function testInvalidOutputs(): void
    {
        $inputs = [
            'connectionExtractor' => [
                'parameters' => [
                    '#token' => 'xxxxx',
                ],
            ],
            'transformation' => [
                'parameters' => [
                    'values' => null,
                ],
            ],
            'main' => [
                'parameters' => [
                    'values' => null,
                ],
            ],
        ];
        $loader = new ExecutionContextLoader($inputs, __DIR__ . '/../../mock/scaffolds/WithOutputsTest');
        $manifestFilePath = __DIR__ . '/../../mock/scaffolds/WithOutputsTest/manifest.json';
        $manifest = JsonHelper::readFile($manifestFilePath);

        $this->expectException(UserException::class);
        $this->expectExceptionMessage(sprintf(
            'The scaffold \'%s\' has same outputs \'%s\'',
            $loader->getExecutionContext()->getScaffoldId(),
            implode(', ', $loader->getExecutionContext()->getManifestOutputs())
        ));

        $this->assertSame($loader->getExecutionContext()->getManifestOutputs(), $manifest['outputs']);

        RequirementsValidator::validateOutputs(
            $manifest['outputs'],
            $loader->getExecutionContext()
        );
    }
}
