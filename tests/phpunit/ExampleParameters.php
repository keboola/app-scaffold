<?php

declare(strict_types=1);

namespace MyComponent\Tests;

class ExampleParameters
{
    public const WRITER_SNOWFLAKE = [
        'keboola.wr_db_snowflake' =>
            [
                'parameters' =>
                    [
                        'db' =>
                            [
                                'host' => 'keboola.snowflakecomputing.com',
                                'database' => 'SAPI_6651',
                                'password' => 'uQrLpYjRmExhQPdhYTFhDR9gcMLgkRK8',
                                'user' => 'SAPI_WORKSPACE_534734414',
                                'schema' => 'WORKSPACE_534734414',
                                'warehouse' => 'KEBOOLA_PROD',
                                'port' => '443',
                                'driver' => 'snowflake',
                            ],
                    ],
            ],
    ];
}
