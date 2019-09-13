<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\ActionConfig;

use Exception;

class CreateComponentActionConfig extends AbstractActionConfig
{
    /** @var string */
    private $KBCComponentId;

    /** @var bool */
    private $saveConfigId = false;

    /** @var array */
    private $payload = [];

    /** @var string */
    private $componentName;

    /**
     * @return CreateComponentActionConfig
     */
    public static function create(array $actionConfig, ?array $parameters): ActionConfigInterface
    {
        $config = new self();
        $config->action = $actionConfig['action'];

        if (array_key_exists('id', $actionConfig)) {
            $config->id = $actionConfig['id'];
        }
        if (array_key_exists('KBCComponentId', $actionConfig)) {
            $config->KBCComponentId = $actionConfig['KBCComponentId'];
        } else {
            throw new Exception('Actions create.component missing KBCComponentId');
        }
        if (array_key_exists('saveConfigId', $actionConfig)) {
            $config->saveConfigId = (bool) $actionConfig['saveConfigId'];
        }
        if (array_key_exists('payload', $actionConfig)) {
            $config->payload = $actionConfig['payload'];
        } else {
            throw new Exception('Actions create.component missing payload');
        }
        if (array_key_exists('name', $config->payload)) {
            $config->componentName = $config->payload['name'];
        } else {
            throw new Exception('Actions create.component payload missing component name');
        }

        if ($config->getId() !== null && array_key_exists($config->getId(), $parameters)) {
            // actions has parameters merge it with paylod configuration
            if (!array_key_exists('configuration', $config->payload)) {
                $config->payload['configuration'] = [];
            }
            $config->payload['configuration'] = array_merge_recursive(
                $config->payload['configuration'],
                $parameters[$config->getId()]
            );
        }

        return $config;
    }

    public function getComponentName(): string
    {
        return $this->componentName;
    }

    public function getKBCComponentId(): string
    {
        return $this->KBCComponentId;
    }

    public function isSaveConfigId(): bool
    {
        return $this->saveConfigId;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
