<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Tests\Operation;

use Keboola\Component\UserException;
use Keboola\ScaffoldApp\Operation\ExecutionContext;
use Keboola\ScaffoldApp\Operation\ExecutionContextValidator;
use PHPUnit\Framework\MockObject\MockObject;

class ExecutionContextValidatorTest extends BaseOperationTestCase
{
    public function testValidateContextMissingRequiredOperations(): void
    {
        /** @var MockObject|ExecutionContext $contextMock */
        $contextMock = self::createMock(ExecutionContext::class);
        $contextMock->expects(self::once())->method('getRequiredOperations')->willReturn([
            'op1',
            'op2',
        ]);
        $contextMock->expects(self::once())->method('getOperationsToExecute')->willReturn([
            'op1',
        ]);
        self::expectException(UserException::class);
        self::expectExceptionMessage('One or more required operations "op2" is missing');
        ExecutionContextValidator::validateContext($contextMock);
    }

    public function testValidateWithDefinitionClass(): void
    {
        /** @var MockObject|ExecutionContext $contextMock */
        $contextMock = self::createMock(ExecutionContext::class);
        $contextMock->expects(self::once())->method('getRequiredOperations')->willReturn([]);
        $contextMock->expects(self::once())->method('getOperationsToExecute')->willReturn([]);
        $contextMock->expects(self::once())->method('getScaffoldDefinitionClass')->willReturn(
            'Keboola\\ScaffoldApp\\Tests\\mock\\ScaffoldDefinitionMock'
        );
        $contextMock->expects(self::once())->method('getScaffoldInputs')->willReturn([
            'ex01' => [],
//                'wr01' => [], missing
        ]);
        self::expectException(UserException::class);
        self::expectExceptionMessage('The child node "wr01" at path "inputs" must be configured.');
        ExecutionContextValidator::validateContext($contextMock);
    }

    public function testValidateWithSchema(): void
    {
        /** @var MockObject|ExecutionContext $contextMock */
        $contextMock = self::createMock(ExecutionContext::class);
        $contextMock->expects(self::once())->method('getRequiredOperations')->willReturn(['op1']);
        $contextMock->expects(self::exactly(2))->method('getOperationsToExecute')->willReturn([
            'op1',
            'op2',
        ]);
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
                return null;
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
        ExecutionContextValidator::validateContext($contextMock);
    }
}
