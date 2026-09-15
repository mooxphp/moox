<?php

declare(strict_types=1);

namespace Moox\PressWiki;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class PressWikiServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('press-wiki')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations();
    }
}
