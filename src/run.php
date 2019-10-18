<?php

declare(strict_types=1);

use Keboola\Component\UserException;
use Keboola\Component\Logger;
use Keboola\ScaffoldApp\Component;

require __DIR__ . '/../vendor/autoload.php';

$logger = new Logger();
try {
    if (getenv('KBC_TOKEN') === false || getenv('KBC_URL') === false) {
        throw new Exception('Env variable "KBC_TOKEN" or "KBC_URL" must be set to run component.');
    }

    $app = new Component($logger);
    $app->execute();
    exit(0);
} catch (UserException $e) {
    $logger->error($e->getMessage());
    exit(1);
} catch (\Throwable $e) {
    $logger->critical(
        get_class($e) . ':' . $e->getMessage(),
        [
            'errFile' => $e->getFile(),
            'errLine' => $e->getLine(),
            'errCode' => $e->getCode(),
            'errTrace' => $e->getTraceAsString(),
            'errPrevious' => $e->getPrevious() ? get_class($e->getPrevious()) : '',
        ]
    );
    exit(2);
}
