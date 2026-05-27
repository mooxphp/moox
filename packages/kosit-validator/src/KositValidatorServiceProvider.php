<?php

declare(strict_types=1);

namespace Moox\KositValidator;

use Moox\Core\MooxServiceProvider;
use Moox\KositValidator\Commands\DoctorCommand;
use Moox\KositValidator\Commands\InstallKositCommand;
use Moox\KositValidator\Commands\ValidateCommand;
use Moox\KositValidator\Services\KositService;
use Spatie\LaravelPackageTools\Package;

class KositValidatorServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('kosit-validator')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasRoutes('web')
            ->hasViews()
            ->hasCommands([InstallKositCommand::class, ValidateCommand::class, DoctorCommand::class])
            ->hasMigrations('create_kosit_validations_table');

        $this->getMooxPackage()
            ->title('moox KositValidator')
            ->released(true)
            ->stability('dev')
            ->category('development')
            ->usedFor([
                'KoSIT Validator CLI wrapper and Filament Plugin for ZUGFeRD / XRechnung XML validation',
            ]);
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(KositService::class);
    }
}
