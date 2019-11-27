<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\SyncActions\UseScaffoldExecutionContext;

use Keboola\Component\UserException;

class RequirementsValidator
{
    public static function validateRequirements(
        array $usedScaffoldManifests,
        ExecutionContext $executionContext
    ): void {
        $diff = array_diff($executionContext->getManifestRequirements(), $usedScaffoldManifests);
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
        array $usedScaffoldManifests,
        ExecutionContext $executionContext
    ): void {
        $intersect = array_intersect($executionContext->getManifestOutputs(), $usedScaffoldManifests);
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
