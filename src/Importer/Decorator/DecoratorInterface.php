<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer\Decorator;

use Keboola\ScaffoldApp\Importer\OperationImport;
use Symfony\Component\Console\Output\OutputInterface;

interface DecoratorInterface
{
    public const USER_ACTION_KEY_PREFIX = '__SCAFFOLD_CHECK__';

    public function __construct(
        OutputInterface $output
    );

    public function getDecoratedProjectImport(
        OperationImport $operationImport
    ): OperationImport;

    public function supports(OperationImport $operationImport): bool;
}
