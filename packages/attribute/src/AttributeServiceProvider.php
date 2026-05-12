<?php

declare(strict_types=1);

namespace Moox\Attribute;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class AttributeServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('attribute')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations('create_attribute_table', 'create_attribute_translations_table')
            ->hasCommands();
    }

    public function packageBooted(): void
    {
    }
}
