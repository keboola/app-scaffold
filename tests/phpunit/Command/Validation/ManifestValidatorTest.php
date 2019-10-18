<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Command\Validation;

use Keboola\ScaffoldApp\Command\Validation\ManifestValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

class ManifestValidatorTest extends TestCase
{
    public function testValidate(): void
    {
        $validator = new ManifestValidator(new SplFileInfo(__DIR__ . '/../../mock/scaffolds/PassThroughTest', '', ''));
        $violations = $validator->validate();

        self::assertCount(0, $violations);
    }
}
