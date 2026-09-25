<?php

declare(strict_types=1);

namespace Moox\Migrate;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class MigrateServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('migrate')
            ->hasConfigFile()
            ->hasTranslations();

        $this->getMooxPackage()
            ->title('moox Migrate')
            ->released(false)
            ->stability('stable')
            ->category('system')
            ->usedFor([
                'listing Laravel migrations and running artisan migrate from Filament',
            ]);
    }
}
