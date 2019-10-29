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
}
