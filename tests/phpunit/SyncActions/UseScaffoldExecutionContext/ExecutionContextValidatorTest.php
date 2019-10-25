<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\SyncActions\UseScaffoldExecutionContext;

use Keboola\Component\UserException;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContext;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContextValidator;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\OperationsContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExecutionContextValidatorTest extends TestCase
{

    public function testValidateWithDefinitionClass(): void
    {
        /** @var MockObject|ExecutionContext $contextMock */
        $contextMock = self::createMock(ExecutionContext::class);
        $contextMock->expects(self::once())->method('getScaffoldDefinitionClass')->willReturn(
            'Keboola\\ScaffoldApp\\Tests\\mock\\ScaffoldDefinitionMock'
        );
        $contextMock->expects(self::once())->method('getScaffoldInputs')->willReturn([
            'ex01' => [],
//                'wr01' => [], missing
        ]);
        self::expectException(UserException::class);
        self::expectExceptionMessage('The child node "wr01" at path "inputs" must be configured.');
        ExecutionContextValidator::validateContext($contextMock, new OperationsContext([], []));
    }

    public function testValidateWithSchema(): void
    {
        /** @var MockObject|ExecutionContext $contextMock */
        $contextMock = self::createMock(ExecutionContext::class);
        $contextMock->expects(self::once())->method('getScaffoldDefinitionClass')->willReturn(null);
        $contextMock->expects(self::exactly(2))->method('getSchemaForOperation')->willReturn([
            'type' => 'object',
            'required' => ['id'],
            'properties' => [
                'id' => [
                    'title' => 'id',
                    'type' => 'integer',
                    'exclusiveMaximum' => 100,
                ],
                'valid' => [
                    'title' => 'isValid',
                    'type' => 'boolean',
                ],
            ],
        ]);
        $contextMock->expects(self::exactly(2))->method('getUserInputsForOperation')->willReturnCallback(function (
            $operation
        ) {
            if ($operation === 'op2') {
                return [];
            }
            // op1
            return [
                'parameters' => [
                    'id' => 200,
                    'valid' => false,
                ],
            ];
        });
        self::expectException(UserException::class);
        self::expectExceptionMessage(
            'One or more operation has invalid parameters:'.PHP_EOL
            . 'op1: Value less or equal than 100 expected, 200 received at #->properties:id.'.PHP_EOL
            . 'op2: Object expected, null received.'
        );
        ExecutionContextValidator::validateContext($contextMock, new OperationsContext(['op1'], ['op1','op2']));
    }
}
