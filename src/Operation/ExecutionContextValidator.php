<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation;

use Keboola\Component\UserException;

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
                'One or more required operations "%s" from scaffold id "%s" is missing.',
                implode(', ', $missingRequieredOpeartions),
                $executionContext->getScaffoldId()
            ));
        }

        // todo valida inputs from UI againts inputs from manifest
    }
}
