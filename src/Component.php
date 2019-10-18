<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp;

use Exception;
use Keboola\Component\BaseComponent;
use Keboola\Component\JsonHelper;
use Keboola\Component\UserException;
use Keboola\ScaffoldApp\Operation\ExecutionContext;
use Keboola\ScaffoldApp\SyncActions\ListScaffoldsAction;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldAction;
use Symfony\Component\Filesystem\Filesystem;

class Component extends BaseComponent
{
    public const SCAFFOLDS_DIR = __DIR__ . '/../scaffolds';

    public function actionListScaffolds(): array
    {
        $action = new ListScaffoldsAction();
        return $action();
    }

    public function actionUseScaffold(): array
    {
        /** @var Config $config */
        $config = $this->getConfig();
        $scaffoldFolder = $this->getScaffoldConfigurationFolder($config->getScaffoldName());
        $scaffoldInputs = $config->getScaffoldInputs();
        $action = new UseScaffoldAction($this->getExecutionContext($scaffoldInputs, $scaffoldFolder));
        return $action();
    }

    private function getExecutionContext(
        array $scaffoldInputs,
        string $scaffoldFolder
    ): ExecutionContext {
        $manifest = JsonHelper::readFile(sprintf('%s/manifest.json', $scaffoldFolder));

        $executionContext = new ExecutionContext(
            $manifest,
            $scaffoldInputs,
            $scaffoldFolder,
            $this->getLogger()
        );
        $executionContext->loadOperations();
        $executionContext->loadOperationsFiles();

        return $executionContext;
    }

    private function getScaffoldConfigurationFolder(
        string $scaffoldId
    ): string {
        $scaffoldFolder = self::SCAFFOLDS_DIR . '/' . $scaffoldId;

        $fs = new Filesystem();
        if (!$fs->exists($scaffoldFolder)) {
            throw new Exception(sprintf('Scaffold "%s" does not exist.', $scaffoldId));
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
