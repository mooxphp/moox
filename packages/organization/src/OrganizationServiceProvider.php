<?php

declare(strict_types=1);

namespace Moox\Organization;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class OrganizationServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('organization')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_organization_types_table',
                'create_organization_type_translations_table',
            ]);
    }
}
