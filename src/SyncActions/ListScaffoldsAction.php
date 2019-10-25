<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\SyncActions;

use Keboola\Component\JsonHelper;
use Symfony\Component\Finder\Finder;

class ListScaffoldsAction
{
    public const NAME = 'listScaffolds';

    public function run(string $scaffoldsDir): array
    {
        $scaffolds = (new Finder())->in($scaffoldsDir)
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
