<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\FunctionalTests;

use Exception;
use Keboola\Component\UserException;
use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecification;
use Keboola\DatadirTests\Exception\DatadirTestsException;
use Keboola\ScaffoldApp\ApiClientStore;
use Keboola\ScaffoldApp\SyncActions\ObjectLister;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldAction;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContextLoader;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class ComponentTest extends AbstractDatadirTestCase
{
    use WorkspaceClearTrait;

    public function testListScaffolds(): void
    {
        $specification = new DatadirTestSpecification(
            __DIR__,
            0,
            null,
            null,
            null
        );
        $tempDatadir = $this->getTempDatadir($specification);
        $data = [
            'action' => 'listScaffolds',
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
        $data = json_decode($process->getOutput(), true);
        self::assertGreaterThan(0, count($data));
        self::assertArrayHasKey('name', $data[1]);
        self::assertArrayHasKey('author', $data[1]);
        self::assertArrayHasKey('id', $data[1]);
        self::assertArrayHasKey('objects', $data[1]);
    }

    protected function runScript(string $datadirPath): Process
    {
        $fs = new Filesystem();

        $script = $this->getScript();
        if (!$fs->exists($script)) {
            throw new DatadirTestsException(sprintf(
                'Cannot open script file "%s"',
                $script
            ));
        }

        $runCommand = [
            'php',
            $script,
        ];
        $runProcess = new Process($runCommand);
        $runProcess->setEnv([
            'KBC_DATADIR' => $datadirPath,
            'KBC_TOKEN' => getenv('KBC_TOKEN'),
            'KBC_URL' => getenv('KBC_URL'),
        ]);
        $runProcess->setTimeout(0);
        $runProcess->run();
        return $runProcess;
    }

    public function testUseScaffold(): void
    {
        if (getenv('KBC_TOKEN') === false) {
            throw new Exception('Env variable "KBC_TOKEN" must be set.');
        }
        if (getenv('KBC_URL') === false) {
            throw new Exception('Env variable "KBC_URL" must be set.');
        }

        $scaffoldId = 'PassThroughTest';
        $this->clearWorkspace(new ApiClientStore(new NullLogger()), $scaffoldId);
        $inputParameters = [
            'connectionWriter' => [
                'parameters' => [
                    '#token' => 'xxxxx',
                ],
            ],
            'snowflakeExtractor' => [
                'parameters' => [
                    'db' => [
                        'host' => 'xxx',
                        'user' => 'xxx',
                        '#password' => 'xxx',
                        'database' => 'xxx',
                        'schema' => 'xxx',
                        'warehouse' => 'xxx',
                    ],
                ],
            ],
            'main' => [
                'parameters' => [],
            ],
        ];

        $scaffoldFolder = __DIR__ . '/../phpunit/mock/scaffolds/' . $scaffoldId;
        $loader = new ExecutionContextLoader($inputParameters, $scaffoldFolder);
        $action = new UseScaffoldAction(
            $loader->getExecutionContext(),
            new ApiClientStore(new NullLogger()),
            new NullLogger
        );

        $process = $action->run();
        $this->assertCount(3, $process);
    }

    public function testUseDependentScaffold(): void
    {
        if (getenv('KBC_TOKEN') === false) {
            throw new Exception('Env variable "KBC_TOKEN" must be set.');
        }
        if (getenv('KBC_URL') === false) {
            throw new Exception('Env variable "KBC_URL" must be set.');
        }

        $store = new ApiClientStore(new NullLogger());
        $objects = ObjectLister::listObjects($store->getStorageApiClient(), $store->getComponentsApiClient());

        foreach ($objects as $name => $object) {
            $this->clearWorkspace(new ApiClientStore(new NullLogger()), $name);
        }

        $inputParameters = [
            'snowflakeExtractor' => [
                'parameters' => [
                    'db' => [
                        'host' => 'xxx',
                        'user' => 'xxx',
                        '#password' => 'xxx',
                        'database' => 'xxx',
                        'schema' => 'xxx',
                        'warehouse' => 'xxx',
                    ],
                ],
            ],
            'main' => [
                'parameters' => [],
            ],
        ];

        $scaffoldFolder = __DIR__ . '/../phpunit/mock/scaffolds/WithOutputsTest';
        $loader = new ExecutionContextLoader($inputParameters, $scaffoldFolder);
        $action = new UseScaffoldAction(
            $loader->getExecutionContext(),
            new ApiClientStore(new NullLogger()),
            new NullLogger
        );

        $process = $action->run();
        $this->assertCount(2, $process);

        $inputParameters = [
            'connectionWriter' => [
                'parameters' => [
                    '#token' => 'xxxxx',
                ],
            ],
            'main' => [
                'parameters' => [],
            ],
        ];

        $scaffoldFolder = __DIR__ . '/../phpunit/mock/scaffolds/WithRequirementsTest';
        $loader = new ExecutionContextLoader($inputParameters, $scaffoldFolder);
        $action = new UseScaffoldAction(
            $loader->getExecutionContext(),
            new ApiClientStore(new NullLogger()),
            new NullLogger
        );

        $process = $action->run();
        $this->assertCount(2, $process);
    }

    public function testCantUseScaffoldWithDifferentRequirements(): void
    {
        if (getenv('KBC_TOKEN') === false) {
            throw new Exception('Env variable "KBC_TOKEN" must be set.');
        }
        if (getenv('KBC_URL') === false) {
            throw new Exception('Env variable "KBC_URL" must be set.');
        }

        $store = new ApiClientStore(new NullLogger());
        $objects = ObjectLister::listObjects($store->getStorageApiClient(), $store->getComponentsApiClient());

        foreach ($objects as $name => $object) {
            $this->clearWorkspace(new ApiClientStore(new NullLogger()), $name);
        }

        $inputParameters = [
            'connectionWriter' => [
                'parameters' => [
                    '#token' => 'xxxxx',
                ],
            ],
            'snowflakeExtractor' => [
                'parameters' => [
                    'db' => [
                        'host' => 'xxx',
                        'user' => 'xxx',
                        '#password' => 'xxx',
                        'database' => 'xxx',
                        'schema' => 'xxx',
                        'warehouse' => 'xxx',
                    ],
                ],
            ],
            'main' => [
                'parameters' => [],
            ],
        ];

        $scaffoldFolder = __DIR__ . '/../phpunit/mock/scaffolds/PassThroughTest';
        $passThroughLoader = new ExecutionContextLoader($inputParameters, $scaffoldFolder);
        $action = new UseScaffoldAction(
            $passThroughLoader->getExecutionContext(),
            new ApiClientStore(new NullLogger()),
            new NullLogger
        );

        $process = $action->run();
        $this->assertCount(3, $process);

        $inputParameters = [
            'connectionWriter' => [
                'parameters' => [
                    '#token' => 'xxxxx',
                ],
            ],
            'main' => [
                'parameters' => [],
            ],
        ];

        $scaffoldFolder = __DIR__ . '/../phpunit/mock/scaffolds/WithRequirementsTest';
        $loader = new ExecutionContextLoader($inputParameters, $scaffoldFolder);
        $action = new UseScaffoldAction(
            $loader->getExecutionContext(),
            new ApiClientStore(new NullLogger()),
            new NullLogger
        );

        $this->expectException(UserException::class);
        $this->expectExceptionMessage(sprintf(
            'The scaffold \'%s\' needs following requirements \'%s\'',
            $loader->getExecutionContext()->getScaffoldId(),
            implode(', ', $loader->getExecutionContext()->getManifestRequirements())
        ));

        $action->run();
    }

    public function testCantCreateScaffoldWithTheSameOutputs(): void
    {
        if (getenv('KBC_TOKEN') === false) {
            throw new Exception('Env variable "KBC_TOKEN" must be set.');
        }
        if (getenv('KBC_URL') === false) {
            throw new Exception('Env variable "KBC_URL" must be set.');
        }

        $store = new ApiClientStore(new NullLogger());
        $objects = ObjectLister::listObjects($store->getStorageApiClient(), $store->getComponentsApiClient());

        foreach ($objects as $name => $object) {
            $this->clearWorkspace(new ApiClientStore(new NullLogger()), $name);
        }

        $inputParameters = [
            'snowflakeExtractor' => [
                'parameters' => [
                    'db' => [
                        'host' => 'xxx',
                        'user' => 'xxx',
                        '#password' => 'xxx',
                        'database' => 'xxx',
                        'schema' => 'xxx',
                        'warehouse' => 'xxx',
                    ],
                ],
            ],
            'main' => [
                'parameters' => [],
            ],
        ];

        $scaffoldFolder = __DIR__ . '/../phpunit/mock/scaffolds/WithOutputsTest';
        $loader = new ExecutionContextLoader($inputParameters, $scaffoldFolder);
        $action = new UseScaffoldAction(
            $loader->getExecutionContext(),
            new ApiClientStore(new NullLogger()),
            new NullLogger
        );

        $process = $action->run();
        $this->assertCount(2, $process);

        $inputParameters = [
            'snowflakeExtractor' => [
                'parameters' => [
                    'db' => [
                        'host' => 'xxx',
                        'user' => 'xxx',
                        '#password' => 'xxx',
                        'database' => 'xxx',
                        'schema' => 'xxx',
                        'warehouse' => 'xxx',
                    ],
                ],
            ],
            'main' => [
                'parameters' => [],
            ],
        ];

        $scaffoldFolder = __DIR__ . '/../phpunit/mock/scaffolds/WithOutputsTest';
        $loader = new ExecutionContextLoader($inputParameters, $scaffoldFolder);
        $action = new UseScaffoldAction(
            $loader->getExecutionContext(),
            new ApiClientStore(new NullLogger()),
            new NullLogger
        );

        $this->expectException(UserException::class);
        $this->expectExceptionMessage(sprintf(
            'The scaffold \'%s\' has same outputs \'%s\'',
            $loader->getExecutionContext()->getScaffoldId(),
            implode(', ', $loader->getExecutionContext()->getManifestOutputs())
        ));

        $action->run();
    }

    public function testUseRequirementsAndOutputsScaffold()
    {
        if (getenv('KBC_TOKEN') === false) {
            throw new Exception('Env variable "KBC_TOKEN" must be set.');
        }
        if (getenv('KBC_URL') === false) {
            throw new Exception('Env variable "KBC_URL" must be set.');
        }

        $store = new ApiClientStore(new NullLogger());
        $objects = ObjectLister::listObjects($store->getStorageApiClient(), $store->getComponentsApiClient());

        foreach ($objects as $name => $object) {
            $this->clearWorkspace(new ApiClientStore(new NullLogger()), $name);
        }

        $inputParameters = [
            'snowflakeExtractor' => [
                'parameters' => [
                    'db' => [
                        'host' => 'xxx',
                        'user' => 'xxx',
                        '#password' => 'xxx',
                        'database' => 'xxx',
                        'schema' => 'xxx',
                        'warehouse' => 'xxx',
                    ],
                ],
            ],
            'main' => [
                'parameters' => [],
            ],
        ];

        $scaffoldFolder = __DIR__ . '/../phpunit/mock/scaffolds/WithRequireOutputsTest';
        $loader = new ExecutionContextLoader($inputParameters, $scaffoldFolder);
        $action = new UseScaffoldAction(
            $loader->getExecutionContext(),
            new ApiClientStore(new NullLogger()),
            new NullLogger
        );

        $process = $action->run();
        $this->assertCount(2, $process);

        $inputParameters = [
            'connectionWriter' => [
                'parameters' => [
                    '#token' => 'xxxxx',
                ],
            ],
            'main' => [
                'parameters' => [],
            ],
        ];

        $scaffoldFolder = __DIR__ . '/../phpunit/mock/scaffolds/WithRequirementsAndOutputsTest';
        $loader = new ExecutionContextLoader($inputParameters, $scaffoldFolder);
        $action = new UseScaffoldAction(
            $loader->getExecutionContext(),
            new ApiClientStore(new NullLogger()),
            new NullLogger
        );

        $process = $action->run();
        $this->assertCount(2, $process);

        $inputParameters = [
            'snowflakeExtractor' => [
                'parameters' => [
                    'db' => [
                        'host' => 'xxx',
                        'user' => 'xxx',
                        '#password' => 'xxx',
                        'database' => 'xxx',
                        'schema' => 'xxx',
                        'warehouse' => 'xxx',
                    ],
                ],
            ],
            'main' => [
                'parameters' => [],
            ],
        ];

        $scaffoldFolder = __DIR__ . '/../phpunit/mock/scaffolds/WithOutputsTest';
        $loader = new ExecutionContextLoader($inputParameters, $scaffoldFolder);
        $action = new UseScaffoldAction(
            $loader->getExecutionContext(),
            new ApiClientStore(new NullLogger()),
            new NullLogger
        );

        $this->expectException(UserException::class);
        $this->expectExceptionMessage(sprintf(
            'The scaffold \'%s\' has same outputs \'%s\'',
            $loader->getExecutionContext()->getScaffoldId(),
            implode(', ', $loader->getExecutionContext()->getManifestOutputs())
        ));

        $process = $action->run();
        $this->assertCount(2, $process);
    }
}
