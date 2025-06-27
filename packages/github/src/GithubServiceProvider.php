<?php

namespace Moox\Github;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class GithubServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('github')
            ->hasRoutes([
                'web',
            ])
            ->hasConfigFile();
    }
}
