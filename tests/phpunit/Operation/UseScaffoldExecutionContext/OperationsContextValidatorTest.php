<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation\UseScaffoldExecutionContext;

use Keboola\Component\UserException;
use Keboola\ScaffoldApp\Operation\UseScaffoldExecutionContext\OperationsContext;
use Keboola\ScaffoldApp\Operation\UseScaffoldExecutionContext\OperationsContextValidator;
use PHPUnit\Framework\TestCase;

class OperationsContextValidatorTest extends TestCase
{
    /**
     * This should pass
     *
     * @doesNotPerformAssertions
     */
    public function testValidate(): void
    {
        $context = new OperationsContext(
            [
                'op1',
                'op2',
            ],
            [
                'op1',
                'op2',
                'op3',
            ]
        );
        OperationsContextValidator::validate($context);
    }

    public function testValidateContextMissingRequiredOperations(): void
    {
        $context = new OperationsContext(
            [
                'op1',
                'op2',
            ],
            ['op1']
        );
        self::expectException(UserException::class);
        self::expectExceptionMessage('One or more required operations "op2" is missing');
        OperationsContextValidator::validate($context);
    }
}
