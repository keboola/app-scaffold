<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\ActionConfig;

use Keboola\ScaffoldApp\ActionConfig\ActionConfigInterface;
use PHPUnit\Framework\TestCase;

class AbstractActionConfigTest extends TestCase
{
    public function testAbstractClassMethod(): void
    {
        $this->assertInstanceOf(
            ActionConfigInterface::class,
            AbstractActionConfigMock::create([], null)
        );
    }

    public function testAbstractClassInternalMethod(): void
    {
        $this->assertNull((AbstractActionConfigMock::create([], null))->getId());
    }
}
