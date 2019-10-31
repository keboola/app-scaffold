<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\FunctionalTests;

use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecification;
use Keboola\DatadirTests\Exception\DatadirTestsException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class ComponentTest extends AbstractDatadirTestCase
{
    public function testUseScaffold(): void
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
            'action' => 'useScaffold',
            'parameters' => [
                'id' => 'CrmMrrSalesforce',
                'inputs' => [
                    [
                        'id' => 'htnsExSalesforceMRR',
                        'values' => null,
                    ],
                    [
                        'id' => 'keboolaWrDbSnowflakeLooker',
                        'values' => null,
                    ],
                    [
                        'id' => 'transformationSalesforceCRM&MRR',
                        'values' => null,
                    ],
                    [
                        'id' => 'orchestrationMRR',
                        'values' => null,
                    ],
                ],
            ],
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', \GuzzleHttp\json_encode($data));
        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
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
        $data  = json_decode($process->getOutput(), true);
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
}
