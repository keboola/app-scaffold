<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\FunctionalTests;

use Exception;
use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecification;
use Keboola\DatadirTests\Exception\DatadirTestsException;
use Keboola\ScaffoldApp\ApiClientStore;
use Keboola\ScaffoldApp\SyncActions\ObjectLister;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class ComponentTest extends AbstractDatadirTestCase
{
    use WorkspaceClearTrait;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('KBC_TOKEN') === false) {
            throw new Exception('Env variable "KBC_TOKEN" must be set.');
        }
        if (getenv('KBC_URL') === false) {
            throw new Exception('Env variable "KBC_URL" must be set.');
        }
    }

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
            'KBC_SCAFFOLDS_DIR' => __DIR__ . '/../phpunit/mock/scaffolds/',
            'KBC_TOKEN' => getenv('KBC_TOKEN'),
            'KBC_URL' => getenv('KBC_URL'),
        ]);
        $runProcess->setTimeout(0);
        $runProcess->run();
        return $runProcess;
    }

    public function testUseScaffold(): void
    {
        $this->clearWorkspace(
            new ApiClientStore(new NullLogger()),
            'CrmMrrSalesforce'
        );
        $specification = new DatadirTestSpecification(
            __DIR__,
            0,
            null,
            null,
            null
        );
        $tempDatadir = $this->getTempDatadir($specification);
        $data = [
            'action' => 'useScaffold',
            'parameters' => [
                'id' => 'PassThroughTest',
                'inputs' => [
                    [
                        'id' => 'connectionWriter',
                        'values' => [
                            'parameters' => [
                                '#token' => 'xxxxx',
                            ],
                        ],
                    ],
                    [
                        'id' => 'snowflakeExtractor',
                        'values' => [
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
                    ],
                    [
                        'id' => 'main',
                        'values' => null,
                    ],
                ],
            ],
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testUseDependentScaffold(): void
    {
        $this->cleanUpWorkspace();

        $specification = new DatadirTestSpecification(
            __DIR__,
            0,
            null,
            null,
            null
        );
        $tempDatadir = $this->getTempDatadir($specification);
        $data = [
            'action' => 'useScaffold',
            'parameters' => [
                'id' => 'WithOutputsTest',
                'inputs' => [
                    [
                        'id' => 'connectionExtractor',
                        'values' => [
                            'parameters' => [
                                '#token' => 'xxxxx',
                            ],
                        ],
                    ],
                    [
                        'id' => 'transformation',
                        'values' => null,
                    ],
                    [
                        'id' => 'main',
                        'values' => null,
                    ],
                ],
            ],
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());

        $specification = new DatadirTestSpecification(
            __DIR__,
            0,
            null,
            null,
            null
        );
        $tempDatadir = $this->getTempDatadir($specification);
        $data = [
            'action' => 'useScaffold',
            'parameters' => [
                'id' => 'WithRequirementsTest',
                'inputs' => [
                    [
                        'id' => 'connectionWriter',
                        'values' => [
                            'parameters' => [
                                '#token' => 'xxxxx',
                            ],
                        ],
                    ],
                    [
                        'id' => 'main',
                        'values' => null,
                    ],
                ],
            ],
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testCantUseScaffoldWithDifferentRequirements(): void
    {
        $this->cleanUpWorkspace();

        $specification = new DatadirTestSpecification(
            __DIR__,
            0,
            null,
            null,
            null
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $data = [
            'action' => 'useScaffold',
            'parameters' => [
                'id' => 'PassThroughTest',
                'inputs' => [
                    [
                        'id' => 'connectionWriter',
                        'values' => [
                            'parameters' => [
                                '#token' => 'xxxxx',
                            ],
                        ],
                    ],
                    [
                        'id' => 'snowflakeExtractor',
                        'values' => [
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
                    ],
                    [
                        'id' => 'main',
                        'values' => null,
                    ],
                ],
            ],
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());

        $specification = new DatadirTestSpecification(
            __DIR__,
            1,
            null,
            null,
            null
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $data = [
            'action' => 'useScaffold',
            'parameters' => [
                'id' => 'WithRequirementsTest',
                'inputs' => [
                    [
                        'id' => 'connectionWriter',
                        'values' => [
                            'parameters' => [
                                '#token' => 'xxxxx',
                            ],
                        ],
                    ],
                    [
                        'id' => 'main',
                        'values' => null,
                    ],
                ],
            ],
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());

        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testCantCreateScaffoldWithTheSameOutputs(): void
    {
        $this->markTestSkipped('We have to enable scaffolds with same outputs for different stacks (AWS/Azure).');
        $this->cleanUpWorkspace();

        $specification = new DatadirTestSpecification(
            __DIR__,
            0,
            null,
            null,
            null
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $data = [
            'action' => 'useScaffold',
            'parameters' => [
                'id' => 'WithOutputsTest',
                'inputs' => [
                    [
                        'id' => 'connectionExtractor',
                        'values' => [
                            'parameters' => [
                                '#token' => 'xxxxx',
                            ],
                        ],
                    ],
                    [
                        'id' => 'transformation',
                        'values' => null,
                    ],
                    [
                        'id' => 'main',
                        'values' => null,
                    ],
                ],
            ],
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());

        $specification = new DatadirTestSpecification(
            __DIR__,
            1,
            null,
            null,
            null
        );
        $tempDatadir = $this->getTempDatadir($specification);

        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    public function testUseRequirementsAndOutputsScaffold(): void
    {
        $this->cleanUpWorkspace();

        $specification = new DatadirTestSpecification(
            __DIR__,
            0,
            null,
            null,
            null
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $data = [
            'action' => 'useScaffold',
            'parameters' => [
                'id' => 'WithRequireOutputsTest',
                'inputs' => [
                    [
                        'id' => 'connectionWriter',
                        'values' => [
                            'parameters' => [
                                '#token' => 'xxxxx',
                            ],
                        ],
                    ],
                    [
                        'id' => 'main',
                        'values' => null,
                    ],
                ],
            ],
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());

        $specification = new DatadirTestSpecification(
            __DIR__,
            0,
            null,
            null,
            null
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $data = [
            'action' => 'useScaffold',
            'parameters' => [
                'id' => 'WithRequirementsAndOutputsTest',
                'inputs' => [
                    [
                        'id' => 'connectionWriter',
                        'values' => [
                            'parameters' => [
                                '#token' => 'xxxxx',
                            ],
                        ],
                    ],
                    [
                        'id' => 'main',
                        'values' => null,
                    ],
                ],
            ],
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());

        $specification = new DatadirTestSpecification(
            __DIR__,
            1,
            null,
            null,
            null
        );
        $tempDatadir = $this->getTempDatadir($specification);

        $data = [
            'action' => 'useScaffold',
            'parameters' => [
                'id' => 'WithOutputsTest',
                'inputs' => [
                    [
                        'id' => 'connectionExtractor',
                        'values' => [
                            'parameters' => [
                                '#token' => 'xxxxx',
                            ],
                        ],
                    ],
                    [
                        'id' => 'transformation',
                        'values' => null,
                    ],
                    [
                        'id' => 'main',
                        'values' => null,
                    ],
                ],
            ],
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    private function cleanUpWorkspace(): void
    {
        $store = new ApiClientStore(new NullLogger());
        $objects = ObjectLister::listObjects($store->getStorageApiClient(), $store->getComponentsApiClient());

        foreach ($objects as $name => $object) {
            $this->clearWorkspace(new ApiClientStore(new NullLogger()), $name);
        }
    }
}
