<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext;

use Keboola\Component\UserException;
use Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext\ExecutionContext;

final class RequirementsValidator
{
    public static function validate(
        array $usedScaffoldManifestsOutputs,
        ExecutionContext $executionContext
    ): void {
        if (count($executionContext->getManifestRequirements()) !== 0) {
            self::validateRequirements(
                $usedScaffoldManifestsOutputs,
                $executionContext
            );
        }

        /*
         * With the introduction of new transformation and Azure stacks, we have to create new scaffolds.
         * There are now the same Scaffolds for Azure and AWS stacks because they have incompatible transformations.
         * This means that we have to allow (hopefully temporarily) scaffolds with same outputs and disable
         * the following check:
        if (count($executionContext->getManifestOutputs()) !== 0) {
            self::validateOutputs(
                $usedScaffoldManifestsOutputs,
                $executionContext
            );
        }
        */
    }

    public static function validateRequirements(
        array $usedScaffoldManifestsOutputs,
        ExecutionContext $executionContext
    ): void {
        $diff = array_diff($executionContext->getManifestRequirements(), $usedScaffoldManifestsOutputs);
        if (count($diff) > 0) {
            throw new UserException(
                sprintf(
                    'The scaffold \'%s\' needs following requirements \'%s\'',
                    $executionContext->getScaffoldId(),
                    implode(', ', $diff)
                )
            );
        }
    }

    public static function validateOutputs(
        array $usedScaffoldManifestsOutputs,
        ExecutionContext $executionContext
    ): void {
        $intersect = array_intersect($executionContext->getManifestOutputs(), $usedScaffoldManifestsOutputs);
        if (count($intersect) > 0) {
            throw new UserException(
                sprintf(
                    'The scaffold \'%s\' has same outputs \'%s\'',
                    $executionContext->getScaffoldId(),
                    implode(', ', $intersect)
                )
            );
        }
    }
}
