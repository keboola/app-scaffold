<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests;

use ReflectionClass;
use Throwable;
use Keboola\ScaffoldApp\Component;
use PHPUnit\Framework\TestCase;

class ComponentTest extends TestCase
{
    public function testActionListScaffolds(): void
    {
        $reflection = new ReflectionClass(Component::class);

        $method = $reflection->getMethod('actionListScaffolds');

        $response = $method->invokeArgs($reflection->newInstanceWithoutConstructor(), []);
        self::assertArrayHasKey('PassThroughTest', $response);
    }

    public function testGetScaffoldConfigurationNotExisting(): void
    {
        $reflection = new ReflectionClass(Component::class);

        $method = $reflection->getMethod('getScaffoldConfiguration');
        $method->setAccessible(true);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Scaffold "NonExistingScaffold" missing scaffold.json configuration file.');
        $method->invokeArgs($reflection->newInstanceWithoutConstructor(), ['NonExistingScaffold']);
    }
}
