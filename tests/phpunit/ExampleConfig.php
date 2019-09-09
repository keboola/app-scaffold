<?php

declare(strict_types=1);

namespace MyComponent\Tests;

class ExampleConfig
{
    public const EXTRACTOR = [
        'type' => 'extractor',
        'sapiComponentId' => 'keboola.csv-import',
        'name' => 'TEST KBC Scaffold Extractor',
    ];
    public const TRANSFORMATION = [
        'type' => 'transformation',
        'sapiComponentId' => 'transformation',
        'id' => 'KBC_TEST_TRANS',
        'name' => 'TEST KBC Scaffold Transformation',
        'changeDescription' => 'TEST KBC Scaffold',
        'rows' => [
            0 =>
                [
                    'name' => 'Company',
                    'description' => '',
                    'configuration' =>
                        [
                            'output' =>
                                [
                                    0 =>
                                        [
                                            'destination' => 'out.c-crm.company',
                                            'primaryKey' =>
                                                [
                                                    0 => 'company_id',
                                                ],
                                            'incremental' => true,
                                            'deleteWhereColumn' => '',
                                            'deleteWhereOperator' => 'eq',
                                            'deleteWhereValues' =>
                                                [
                                                ],
                                            'source' => 'out_company',
                                        ],
                                ],
                            'queries' =>
                                [
                                ],
                            'input' =>
                                [
                                    0 =>
                                        [
                                            'source' => 'in.c-salesforce.account',
                                            'destination' => 'account',
                                            'datatypes' =>
                                                [
                                                ],
                                            'whereColumn' => '',
                                            'whereValues' =>
                                                [
                                                ],
                                            'whereOperator' => 'eq',
                                            'columns' =>
                                                [
                                                ],
                                            'loadType' => 'clone',
                                        ],
                                ],
                            'name' => 'Company',
                            'backend' => 'snowflake',
                            'type' => 'simple',
                            'phase' => 1,
                            'description' => '',
                        ],
                ],
        ],
    ];
    public const WRITER = [
        'sapiComponentId' => 'keboola.wr-db-snowflake',
        'type' => 'writer',
        'name' => 'KBC Scaffold TEST WRITER',
        'configuration' =>
            [
                'parameters' =>
                    [
                        'tables' =>
                            [
                                [
                                    'dbName' => 'LOCATION',
                                    'export' => true,
                                    'tableId' => 'out.c-reviews.location',
                                    'items' =>
                                        [
                                            [
                                                'name' => 'location_id',
                                                'dbName' => 'LOCATION_ID',
                                                'type' => 'string',
                                                'size' => '255',
                                                'nullable' => false,
                                                'default' => '',
                                            ],
                                        ],
                                    'primaryKey' =>
                                        [
                                            'LOCATION_ID',
                                        ],
                                ],
                            ],
                    ],
                'storage' =>
                    [
                        'input' =>
                            [
                                'tables' =>
                                    [
                                        [
                                            'source' => 'out.c-reviews.location',
                                            'destination' => 'out.c-reviews.location.csv',
                                            'columns' =>
                                                [
                                                    'location_id',
                                                ],
                                        ],
                                    ],
                            ],
                    ],
            ],
    ];
    public const APPLICATION = [
        'type' => 'application',
        'sapiComponentId' => 'geneea.nlp-analysis-v2',
        'name' => 'KBC Scaffold test application',
        'configuration' =>
            [
                'storage' =>
                    [
                        'input' =>
                            [
                                'tables' =>
                                    [
                                        0 =>
                                            [
                                                'source' => 'out.c-reviews.pre_nlp_review_response',
                                                'destination' => 'out.c-reviews.pre_nlp_review_response',
                                                'changed_since' => '-2 days',
                                                'columns' =>
                                                    [
                                                        0 => 'review_response_id',
                                                    ],
                                            ],
                                    ],
                            ],
                    ],
                'parameters' =>
                    [
                        'language' => 'en',
                        'columns' =>
                            [
                                'id' =>
                                    [
                                        0 => 'review_response_id',
                                    ],
                            ],
                        'analysis_types' =>
                            [
                                0 => 'tags',
                                1 => 'entities',
                                2 => 'sentiment',
                                3 => 'relations',
                            ],
                        'domain' => 'hcc',
                        'use_beta' => false,
                        'correction' => 'none',
                        'diacritization' => 'none',
                        'advanced' =>
                            [
                            ],
                    ],
            ],
    ];
    public const ORCHESTRATION = [
        'type' => 'orchestration',
        'name' => 'TEST Orchestration',
        'tasks' =>
            [
                [
                    'component' => 'transformation',
                    'id' => 'transformation1',
                    'action' => 'run',
                    'phase' => 'Transformation 01',
                ],
                [
                    'component' => 'transformation',
                    'id' => 'transformation2',
                    'action' => 'run',
                    'phase' => 'Transformation 02',
                ],
            ],
    ];
}
