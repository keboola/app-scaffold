<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\SyncActions;

use Keboola\Component\JsonHelper;
use Keboola\ScaffoldApp\Component;
use Symfony\Component\Finder\Finder;

class ListScaffoldsAction
{
    public const NAME = 'actionListScaffolds';

    public function __invoke(): array
    {
        $finder = new Finder();

        // CreateConfiguration
        $scaffolds = $finder->in(Component::SCAFFOLDS_DIR)
            ->directories()->depth(0);

        $response = [];

        foreach ($scaffolds->getIterator() as $directory) {
            $manifest = JsonHelper::readFile(sprintf(
                '%s/manifest.json',
                $directory->getPathname()
            ));
            $manifest['id'] = $directory->getFilename();
            $response[] = $manifest;
        }

        return $response;
    }
}
