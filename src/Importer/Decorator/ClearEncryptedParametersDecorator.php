<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Importer\Decorator;

use Keboola\ScaffoldApp\Importer\OperationImport;

class ClearEncryptedParametersDecorator implements DecoratorInterface
{
    private const CRYPTED_KEY_PREFIX = '#';

    public function getDecoratedProjectImport(
        OperationImport $operationImport
    ): OperationImport {
        $payload = $this->clearCryptedParametersRecursive($operationImport->getPayload());

        return new OperationImport(
            $operationImport->getScaffoldId(),
            $operationImport->getOperationId(),
            $operationImport->getComponentId(),
            $payload,
            $operationImport->getTask(),
            $operationImport->getConfigurationRows()
        );
    }

    private function clearCryptedParametersRecursive(array $payload): array
    {
        foreach ($payload as $key => &$value) {
            if (is_int($key)) {
                continue;
            }

            if (is_array($value)) {
                $value = $this->clearCryptedParametersRecursive($value);
            } else {
                if ($this->isValueCrypted($key)) {
                    // clear value
                    $value = self::USER_ACTION_KEY_PREFIX;
                }
            }
        }

        return $payload;
    }

    private function isValueCrypted(string $key): bool
    {
        return self::CRYPTED_KEY_PREFIX === substr($key, 0, 1);
    }

    public function supports(OperationImport $operationImport): bool
    {
        $payload = $operationImport->getPayload();
        if (!isset($payload['configuration'])) {
            return false;
        }

        if (!isset($payload['configuration']['parameters'])) {
            return false;
        }

        return true;
    }
}
