<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\SyncActions;

use Keboola\ScaffoldApp\SyncActions\ObjectLister;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\Components;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ObjectListerTest extends TestCase
{
    public function testListObjects(): void
    {
        /** @var Components|MockObject $components */
        $components = self::getMockBuilder(Components::class)
            ->disableOriginalConstructor()
            ->setMethods(['listComponents'])
            ->getMock();
        $components->method('listComponents')
            ->willReturn([
                [
                    'id' => 'orchestrator',
                    'configurations' => [
                        [
                            'id' => 'config1',
                            'description' => 'Some text |ScaffoldId: ScaffoldA| Some other text',
                        ],
                    ],
                ],
                [
                    'id' => 'keboola.ex-db-snowflake',
                    'configurations' => [
                        [
                            'id' => 'config2',
                            'description' => 'Some text |ScaffoldId: ScaffoldA| Some other text',
                        ],
                        [
                            'id' => 'config3',
                            'description' => 'Some text |something-else: ScaffoldA| Some other text',
                        ],
                    ],
                ],
            ]);
        /** @var Client|MockObject $client */
        $client = self::getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['apiGet'])
            ->getMock();
        $client->method('apiGet')
            ->withConsecutive(
                ['storage/search/tables?metadataKey=scaffold.id'],
                ['storage/tables/in.c-main.table1/metadata'],
                ['storage/tables/in.c-main.table2/metadata'],
                ['storage/tables/in.c-main.table3/metadata']
            )
            ->willReturnOnConsecutiveCalls(
                [
                    [
                        'id' => 'in.c-main.table1',
                    ],
                    [
                        'id' => 'in.c-main.table2',
                    ],
                    [
                        'id' => 'in.c-main.table3',
                    ],
                ],
                [
                    [
                        'key' => 'scaffold.id',
                        'value' => 'ScaffoldA',
                    ],
                ],
                [
                    [
                        'key' => 'scaffold.id',
                        'value' => 'ScaffoldA',
                    ],
                ],
                [
                    [
                        'key' => 'scaffold.id',
                        'value' => 'ScaffoldB',
                    ],
                ]
            );

        $objects = ObjectLister::listObjects($client, $components);
        self::assertEquals(
            [
                'ScaffoldA' => [
                    'tables' => [
                        'in.c-main.table1',
                        'in.c-main.table2',
                    ],
                    'configurations' => [
                        [
                            'configurationId' => 'config1',
                            'componentId' => 'orchestrator',
                        ],
                        [
                            'configurationId' => 'config2',
                            'componentId' => 'keboola.ex-db-snowflake',
                        ],
                    ],
                ],
                'ScaffoldB' => [
                    'tables' => [
                        'in.c-main.table3',
                    ],
                ],
            ],
            $objects
        );
    }

    public function testListScaffolds(): void
    {
        /** @var Client|MockObject $client */
        $client = self::getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['verifyToken', 'getApiUrl', 'indexAction'])
            ->getMock();
        $client->method('verifyToken')
            ->willReturn(
                [
                    'id' => '123',
                    'owner' => [
                        'id' => '456',
                        'name' => 'Test',
                        'features' => [
                            'new-transformations-only'
                        ]
                    ]
                ]
            );
        $client->method('getApiUrl')
            ->willReturn('https://connection.north-europe.azure.keboola.com/');
        $client->method('indexAction')
            ->willReturn([
                'host' => 'whatever',
                'api' => 'storage',
                'components' => [
                    ['id' => 'kds-team.ex-bitbucket'],
                    ['id' => 'keboola.snowflake-transformation'],
                    ['id' => 'keboola.wr-snowflake-blob-storage'],
                    ['id' => 'orchestrator'],
                ],
            ]);

        $objects = ObjectLister::listScaffolds($client, __DIR__ . '/../../../scaffolds/');
        $ids = [];
        foreach ($objects as $scaffold) {
            $ids[] = $scaffold['id'];
        }
        self::assertEquals(['AzBitbucketDevops'], $ids);
    }

    public function testListScaffoldsLegacy(): void
    {
        /** @var Client|MockObject $client */
        $client = self::getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['verifyToken', 'getApiUrl', 'indexAction'])
            ->getMock();
        $client->method('verifyToken')
            ->willReturn(
                [
                    'id' => '123',
                    'owner' => [
                        'id' => '456',
                        'name' => 'Test',
                        'features' => []
                    ]
                ]
            );
        $client->method('getApiUrl')
            ->willReturn('https://connection.keboola.com/');
        $client->method('indexAction')
            ->willReturn([
                'host' => 'whatever',
                'api' => 'storage',
                'components' => [
                    ['id' => 'kds-team.ex-bitbucket'],
                    ['id' => 'kds-team.ex-hubspot-crm'],
                    ['id' => 'htns.ex-salesforce'],
                    ['id' => 'keboola.ex-pipedrive'],
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
            ]);

        $objects = ObjectLister::listScaffolds($client, __DIR__ . '/../../../scaffolds/');
        $ids = [];
        foreach ($objects as $scaffold) {
            $ids[] = $scaffold['id'];
        }
        self::assertEquals(
            ['BitbucketDevops', 'CrmHubSpot', 'CrmMrrSalesforce', 'CrmPipedrive', 'CrmSalesforce',
            'CrmSalesforceExternal', 'CrmSnowflakeWriterExternal', 'GitHubDevops', 'MrrSalesforce',
            'PaymoTimeTracking', 'ProjectManagementAsana', 'ReviewTrackersHospitality', 'ZendeskSupport'],
            $ids
        );
    }
}
