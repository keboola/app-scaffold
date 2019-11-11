<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation\Decorator\CreateConfiguration;

use Keboola\ScaffoldApp\Operation\Decorator\CreateConfiguration\ZendeskDecorator;
use Keboola\StorageApi\Options\Components\Configuration;
use PHPUnit\Framework\TestCase;

class ZendeskDecoratorTest extends TestCase
{
    public function testGetDecoratedConfiguration(): void
    {
        $configuration = new Configuration();
        $configuration->setConfigurationId('keboola.ex-othercomponent');
        $configuration->setConfiguration([
            'parameters' => [
                'config' => [
                    'domain' => 'keboola',
                    'usermail' => 'dev@keboola.com',
                    '#password' => 'password',
                ],
            ],
        ]);

        $decorator = new ZendeskDecorator();
        $configuration = $decorator->getDecoratedConfiguration($configuration);

        self::assertSame(
            [
                'parameters' => [
                    'config' => [
                        'domain' => 'keboola',
                        'usermail' => 'dev@keboola.com',
                        '#password' => 'password',
                        'username' => 'dev@keboola.com/token',
                    ],
                ],
            ],
            $configuration->getConfiguration()
        );
    }

    public function testSupports(): void
    {
        $configuration = new Configuration();
        $configuration->setComponentId('keboola.ex-zendesk');
        self::assertTrue((new ZendeskDecorator)->supports($configuration));

        $configuration = new Configuration();
        $configuration->setComponentId('keboola.ex-othercomponent');
        self::assertFalse((new ZendeskDecorator)->supports($configuration));
    }
}
