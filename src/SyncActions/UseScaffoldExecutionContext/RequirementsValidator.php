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
        if ($executionContext->getManifestRequirements()) {
            self::validateRequirements(
                $usedScaffoldManifestsOutputs,
                $executionContext
            );
        }

        if ($executionContext->getManifestOutputs()) {
            self::validateOutputs(
                $usedScaffoldManifestsOutputs,
                $executionContext
            );
        }
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
