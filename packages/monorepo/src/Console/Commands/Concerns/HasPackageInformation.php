<?php

namespace Moox\Monorepo\Console\Commands\Concerns;

trait HasPackageInformation
{
    protected function getPackageInformation()
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $packages = $composer['require'];

        return $packages;
    }
}
