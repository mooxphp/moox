<?php

declare(strict_types=1);

namespace Moox\Tree;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class TreeServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('draft')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations('create_drafts_table', 'create_draft_translations_table')
            ->hasCommands();
    }
}
