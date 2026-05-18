<?php

declare(strict_types=1);

namespace Moox\Scopes;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ScopesServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('scopes')
            ->hasConfigFile()
            ->hasTranslations();
    }
}
