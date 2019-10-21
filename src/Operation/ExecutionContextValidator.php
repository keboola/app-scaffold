<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Keboola\Component\UserException;
use Keboola\ScaffoldApp\ScaffoldInputDefinitionInterface;
use Keboola\ScaffoldApp\ScaffoldInputsDefinition;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Exception;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ExecutionContextValidator
{
    public static function validateContext(ExecutionContext $executionContext): void
    {
        $missingRequieredOpeartions = array_diff(
            $executionContext->getRequiredOperations(),
            $executionContext->getOperationsToExecute()
        );
        if (!empty($missingRequieredOpeartions)) {
            throw new UserException(sprintf(
                'One or more required operations "%s" is missing.',
                implode(', ', $missingRequieredOpeartions)
            ));
        }

        self::validateSchema($executionContext);
    }

    private static function validateSchema(ExecutionContext $executionContext): void
    {
        $scaffoldDefinitionClass = $executionContext->getScaffoldDefinitionClass();

        if ($scaffoldDefinitionClass === null || !class_exists($scaffoldDefinitionClass)) {
            self::validateWithJsonSchema($executionContext);
        } else {
            self::validateWithDefinitionClass($executionContext, new $scaffoldDefinitionClass);
        }
    }

    private static function validateWithJsonSchema(
        ExecutionContext $executionContext
    ): void {
        $operationsToExecute = $executionContext->getOperationsToExecute();
        $validationErrors = [];
        foreach ($operationsToExecute as $operationid) {
            $schema = $executionContext->getSchemaForOperation($operationid);
            if ($schema === null) {
                continue;
            }
            $context = new Context();
            $context->version = Schema::VERSION_DRAFT_04;
            // we need schema as object
            $schema = Schema::import(json_decode(json_encode($schema)));
            $input = $executionContext->getUserInputsForOperation($operationid);
            if (empty($input) || !array_key_exists('parameters', $input)) {
                // set default and continue to validation
                $input['parameters'] = null;
            }

            try {
                // we need input as object
                $input = json_decode(json_encode($input['parameters']));
                $schema->in($input);
            } catch (Exception $e) {
                $validationErrors[$operationid] = sprintf(
                    'Operation %s has invalid parameters: %s.',
                    $operationid,
                    $e->getMessage()
                );
            }
        }

        if (!empty($validationErrors)) {
            throw new UserException(sprintf(
                'One or more operation has invalid parameters: %s',
                implode(PHP_EOL, $validationErrors)
            ));
        }
    }

    private static function validateWithDefinitionClass(
        ExecutionContext $executionContext,
        ScaffoldInputDefinitionInterface $scaffoldDefinitionClass
    ): void {
        $scaffoldInputsDefinition = new ScaffoldInputsDefinition($scaffoldDefinitionClass);
        $processor = new Processor();
        try {
            $processor->processConfiguration(
                $scaffoldInputsDefinition,
                [$executionContext->getScaffoldInputs()]
            );
        } catch (InvalidConfigurationException $e) {
            throw new UserException($e->getMessage(), 0, $e);
        }
    }
}
