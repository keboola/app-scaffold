<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Exception;
use Keboola\Component\BaseComponent;
use Keboola\Component\JsonHelper;
use Keboola\Component\UserException;
use Keboola\ScaffoldApp\SyncActions\ObjectLister;
use Keboola\ScaffoldApp\SyncActions\RequirementsValidator;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContextLoader;
use Keboola\ScaffoldApp\SyncActions\ListScaffoldsAction;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldAction;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Component extends BaseComponent
{
    public const SCAFFOLDS_DIR = __DIR__ . '/../scaffolds';

    public function actionListScaffolds(): array
    {
        $action = new ListScaffoldsAction();
        return $action->run(self::SCAFFOLDS_DIR, new ApiClientStore($this->getLogger()));
    }

    public function actionUseScaffold(): array
    {
        /** @var Config $config */
        $config = $this->getConfig();
        $scaffoldFolder = $this->getScaffoldConfigurationFolder($config->getScaffoldName());
        $scaffoldInputs = $config->getParsedInputs();
        $loader = new ExecutionContextLoader($scaffoldInputs, $scaffoldFolder);
        $apiClientStore = new ApiClientStore($this->getLogger());

        $action = new UseScaffoldAction(
            $loader->getExecutionContext(),
            $apiClientStore,
            $this->getLogger()
        );
        return $action->run();
    }

    private function getScaffoldConfigurationFolder(
        string $scaffoldId
    ): string {
        if (getenv('KBC_SCAFFOLDS_DIR') !== '') {
            $scaffoldFolder = getenv('KBC_SCAFFOLDS_DIR') . '/' . $scaffoldId;
        } else {
            $scaffoldFolder = self::SCAFFOLDS_DIR . '/' . $scaffoldId;
        }

        $fs = new Filesystem();
        if (!$fs->exists($scaffoldFolder)) {
            throw new UserException(sprintf('Scaffold "%s" does not exist.', $scaffoldId));
        }

        return $scaffoldFolder;
    }

    public function getConfigClass(): string
    {
        return Config::class;
    }

    public function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }

    public function getSyncActions(): array
    {
        return [
            ListScaffoldsAction::NAME => 'actionListScaffolds',
            UseScaffoldAction::NAME => 'actionUseScaffold',
        ];
    }

    protected function run(): void
    {
        throw new UserException('Can be used only for sync actions {listScaffolds,useScaffold}.');
    }
}
