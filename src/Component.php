<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Exception;
use Keboola\Component\BaseComponent;
use Keboola\Component\JsonHelper;
use Keboola\Component\UserException;
use Keboola\StorageApi\Client;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class Component extends BaseComponent
{
    public const SCAFFOLDS_DIR = __DIR__ . '/../scaffolds';

    public function actionListScaffolds(): array
    {
        $finder = new Finder();

        // CreateConfiguration
        $scaffolds = $finder->in(self::SCAFFOLDS_DIR)
            ->directories()->depth(0);

        $response = [];

        foreach ($scaffolds->getIterator() as $directory) {
            $response[$directory->getFilename()] = JsonHelper::readFile(sprintf(
                '%s/manifest.json',
                $directory->getPathname()
            ));
        }

        return $response;
    }

    public function actionUseScaffold(): array
    {
        /** @var Config $config */
        $config = $this->getConfig();
        $scaffoldInputs = $config->getScaffoldInputs();
        $scaffoldConfiguration = $this->getScaffoldConfiguration($config->getScaffoldName());

        $client = new Client(
            [
                'token' => getenv('KBC_TOKEN'),
                'url' => getenv('KBC_URL'),
                'logger' => $this->getLogger(),
            ]
        );

        $app = new App(
            $scaffoldConfiguration,
            $scaffoldInputs,
            $client,
            OrchestratorClientFactory::createForStorageApi($client),
            EncryptionClient::createForStorageApi($client),
            $this->getLogger()
        );
        $app->run();

        return $app->getCreatedConfigurations();
    }

    // load scaffold scaffold.json file
    private function getScaffoldConfiguration(string $scaffoldName): array
    {
        $scaffoldFolder = self::SCAFFOLDS_DIR . '/' . $scaffoldName;
        $scaffoldConfigFile = $scaffoldFolder . '/scaffold.json';
        $fs = new Filesystem();
        if (!$fs->exists($scaffoldConfigFile)) {
            throw new Exception(sprintf('Scaffold "%s" missing scaffold.json configuration file.', $scaffoldName));
        }
        if (!$fs->exists($scaffoldFolder . '/manifest.json')) {
            throw new Exception(sprintf('Scaffold "%s" missing manifest.json file.', $scaffoldName));
        }
        return JsonHelper::readFile($scaffoldConfigFile);
    }

    public function getSyncActions(): array
    {
        return [
            'listScaffolds' => 'actionListScaffolds',
            'useScaffold' => 'actionUseScaffold',
        ];
    }

    protected function run(): void
    {
        throw new UserException('Can be used only for sync actions {listScaffolds,useScaffold}.');
    }
}
