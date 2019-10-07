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
        $reflection = new ReflectionClass(Component::class);

        $method = $reflection->getMethod('getScaffoldConfiguration');
        $method->setAccessible(true);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Scaffold name: NonExistingScaffold missing scaffold.json configuration file.');
        $method->invokeArgs($reflection->newInstanceWithoutConstructor(), ['NonExistingScaffold']);
    }
}
