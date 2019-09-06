<?php

declare(strict_types=1);

namespace MyComponent;

use Keboola\Component\Config\BaseConfigDefinition;
use Scaffolds\ReviewsReviewTrackers\ConfigDefinition as ReviewsReviewTrackersConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    public const PATH_SEPARATOR = '#';

    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $def = new ReviewsReviewTrackersConfigDefinition();
        $parametersNode->append($def->getConfigTreeBuilder()->getRootNode());
        // @formatter:on
        return $parametersNode;
    }
}
