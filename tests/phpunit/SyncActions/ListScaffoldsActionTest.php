<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\SyncActions;

use Keboola\ScaffoldApp\SyncActions\ListScaffoldsAction;
use Keboola\ScaffoldApp\Tests\Operation\BaseOperationTestCase;
use Symfony\Component\Finder\Finder;

class ListScaffoldsActionTest extends BaseOperationTestCase
{
    public const SCAFFOLD_DIR = __DIR__ . '/../mock/scaffolds';

    public function testAction(): void
    {
        $action = new ListScaffoldsAction();
        $clientMock = $this->getMockStorageApiClient();
        $clientMock->method('searchTables')->willReturn([]);
        $clientMock->method('verifyToken')->willReturn(['owner' => ['features' => []]]);
        $clientMock->method('getApiUrl')
            ->willReturn('https://connection.keboola.com/');
        $clientMock->method('indexAction')->willReturn(
            [
                'host' => 'whatever',
                'api' => 'storage',
                'components' => [
                    ['id' => 'keboola.ex-storage'],
                    ['id' => 'geneea.nlp-analysis-v2'],
                    ['id' => 'keboola.wr-storage'],
                    ['id' => 'orchestration'],
                    ['id' => 'htns.ex-salesforce'],
                    ['id' => 'keboola.ex-github'],
                    ['id' => 'kds-team.ex-paymo'],
                    ['id' => 'leochan.ex-asana'],
                    ['id' => 'kds-team.ex-reviewtrackers'],
                    ['id' => 'keboola.ex-zendesk'],
                    ['id' => 'keboola.snowflake-transformation'],
                    ['id' => 'keboola.wr-db-snowflake'],
                    ['id' => 'orchestrator'],
                    ['id' => 'transformation'],
                    ['id' => 'geneea.nlp-analysis-v2'],
                ],
            ]
        );
        $componentMock = $this->getMockComponentsApiClient();
        $componentMock->method('listComponents')->willReturn([]);
        $response = $action->run(
            self::SCAFFOLD_DIR,
            $this->getApiClientStore($clientMock, $componentMock)
        );
        $scaffolds = (new Finder())->in(self::SCAFFOLD_DIR)
            ->directories()->depth(0);

        self::assertCount(6, $response);
        foreach ($response as $scaffold) {
            self::assertArrayHasKey('id', $scaffold);
        }
    }
}
