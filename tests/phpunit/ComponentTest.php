<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests;

use ReflectionClass;
use Keboola\ScaffoldApp\Component;
use PHPUnit\Framework\TestCase;

class ComponentTest extends TestCase
{
    public function testActionListScaffolds(): void
    {
        $reflection = new ReflectionClass(Component::class);

        $method = $reflection->getMethod('actionListScaffolds');

        $response = $method->invokeArgs($reflection->newInstanceWithoutConstructor(), []);
        foreach ($response as $scaffold) {
            self::assertArrayHasKey('id', $scaffold);
        }
    }
}
