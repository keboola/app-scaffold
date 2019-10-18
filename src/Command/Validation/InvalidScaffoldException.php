<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Command\Validation;

use Exception;
use SplFileInfo;
use Throwable;

class InvalidScaffoldException extends Exception
{
    public function __construct(
        SplFileInfo $scaffoldDir,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {

        $scaffoldName = $scaffoldDir->getFilename();
        $message = <<<EOL
Scaffold id "$scaffoldName" is no valid.
Validation failed with:
$message
EOL;

        parent::__construct($message, $code, $previous);
    }
}
