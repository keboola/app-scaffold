<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests;

use Throwable;
use Keboola\ScaffoldApp\Component;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ComponentTest extends TestCase
{
    public function testGetScaffoldConfigurationNotExisting(): void
    {
        $component = new Component(new NullLogger);

        $reflection = new \ReflectionClass(get_class($component));
        $method = $reflection->getMethod('getScaffoldConfiguration');
        $method->setAccessible(true);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Scaffold name: NonExistingScaffold does\'t exists.');
        $method->invokeArgs($component, ['NonExistingScaffold']);
    }
}
