<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Command\Validation;

use Keboola\ScaffoldApp\Command\Validation\ManifestValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Validator\ConstraintViolation;

class ManifestValidatorTest extends TestCase
{
    public function testInvalidManifest(): void
    {
        $validator = new ManifestValidator(
            new SplFileInfo(__DIR__ . '/../../mock/scaffolds/WrongManifestTest', '', '')
        );
        $violations = $validator->validate();

        self::assertCount(13, $violations);

        $errorsActual = [];
        /** @var ConstraintViolation $violation */
        foreach ($violations as $violation) {
            if (isset($errorsActual[$violation->getPropertyPath()])) {
                if (!is_array($errorsActual[$violation->getPropertyPath()])) {
                    $errorsActual[$violation->getPropertyPath()] = [$errorsActual[$violation->getPropertyPath()]];
                }
                $errorsActual[$violation->getPropertyPath()][] = $violation->getMessage();
            } else {
                $errorsActual[$violation->getPropertyPath()] = $violation->getMessage();
            }
        }
        self::assertSame([
            '[author]' => 'This value should not be blank.',
            '[name]' => 'This value should not be blank.',
            '[description]' => 'This value is too short. It should have 20 characters or more.',
            '[inputs][0][schema][type]' => 'This field is missing.',
            '[inputs][0][schema][required]' => 'This field is missing.',
            '[inputs][0][schema][properties]' => 'This field is missing.',
            '[inputs][1][componentId]' => 'This field is missing.',
            '[inputs][1][name]' => 'This field is missing.',
            '[inputs][1][required]' => 'This field is missing.',
            '[inputs][2][schema][required]' => 'This field is missing.',
            '[inputs][2][schema][properties]' => 'This field is missing.',
            '[inputs]' => [
                0 => 'Scaffold inputs missing this operations: snowflakeExtractor',
                // phpcs:disable
                1 => 'Json schema for component "invalid schema" is not valid: "No valid results for anyOf {
  0: Enum failed, enum: ["array","boolean","integer","null","number","object","string"], data: "any" at #->properties:type->anyOf[0]
  1: Array expected, "any" received at #->properties:type->anyOf[1]
} at #->properties:type".',
                // phpcs:enable
            ],
        ], $errorsActual);
    }

    public function testValidate(): void
    {
        $validator = new ManifestValidator(new SplFileInfo(__DIR__ . '/../../mock/scaffolds/PassThroughTest', '', ''));
        $violations = $validator->validate();

        self::assertCount(0, $violations);
    }
}
