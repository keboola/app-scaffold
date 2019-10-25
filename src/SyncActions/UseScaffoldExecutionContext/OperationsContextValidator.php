<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext;

use Keboola\Component\UserException;

class OperationsContextValidator
{
    public static function validate(OperationsContext $context): void
    {
        $missingRequiredOperations = array_diff(
            $context->getRequiredOperations(),
            $context->getOperationsToExecute()
        );
        if (!empty($missingRequiredOperations)) {
            throw new UserException(sprintf(
                'One or more required operations "%s" is missing.',
                implode(', ', $missingRequiredOperations)
            ));
        }
    }
}
