<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests;

use ReflectionClass;
use Throwable;
use Keboola\ScaffoldApp\Component;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ComponentTest extends TestCase
{
    public function testGetScaffoldConfigurationNotExisting(): void
    {
        putenv('KBC_DATADIR=' . __DIR__ . '/mock/datadir_empty_config');
        $component = new Component(new NullLogger);

        $reflection = new ReflectionClass(get_class($component));

        /** @var ReflectionClass $refParentClass */
        $refParentClass = $reflection->getParentClass();

        $prop = $refParentClass->getProperty('dataDir');
        $prop->setAccessible(true);
        $prop->setValue($component, __DIR__);

        $method = $reflection->getMethod('getScaffoldConfiguration');
        $method->setAccessible(true);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Scaffold name: NonExistingScaffold does\'t exists.');
        $method->invokeArgs($component, ['NonExistingScaffold']);
    }
}
