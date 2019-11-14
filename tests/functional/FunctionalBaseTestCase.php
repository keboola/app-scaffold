<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\FunctionalTests;

use Exception;
use Keboola\ScaffoldApp\ApiClientStore;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

abstract class FunctionalBaseTestCase extends TestCase
{
    use WorkspaceClearTrait;
    /**
     * @var ApiClientStore
     */
    protected $apiClients;

    protected function setUp(): void
    {
        if (getenv('KBC_TOKEN') === false) {
            throw new Exception('Env variable "KBC_TOKEN" must be set.');
        }
        if (getenv('KBC_URL') === false) {
            throw new Exception('Env variable "KBC_URL" must be set.');
        }

        $this->apiClients = new ApiClientStore(new NullLogger());
    }
}
