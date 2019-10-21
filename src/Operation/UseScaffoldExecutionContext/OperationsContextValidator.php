<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Operation\UseScaffoldExecutionContext;

use Keboola\Component\UserException;

class OperationsContextValidator
{
    public static function validate(OperationsContext $context): void
    {
        $missingRequieredOpeartions = array_diff(
            $context->getRequiredOperations(),
            $context->getOperationsToExecute()
        );
        if (!empty($missingRequieredOpeartions)) {
            throw new UserException(sprintf(
                'One or more required operations "%s" is missing.',
                implode(', ', $missingRequieredOpeartions)
            ));
        }
    }
}
