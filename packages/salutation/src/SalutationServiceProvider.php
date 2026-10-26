<?php

declare(strict_types=1);

namespace Moox\Salutation;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class SalutationServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('salutation')
            ->hasConfigFile('honorific-title')
            ->hasTranslations()
            ->hasMigrations([
                'create_salutations_table',
                'create_salutation_translations_table',
                'create_honorific_titles_table',
                'create_honorific_title_translations_table',
            ]);
    }
}
