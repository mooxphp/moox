<?php

declare(strict_types=1);

namespace Moox\KositValidator;

use Moox\Core\MooxServiceProvider;
use Moox\Core\Support\MorphPivot\MorphPivotRelationRegistry;
use Moox\KositValidator\Commands\DoctorCommand;
use Moox\KositValidator\Commands\InstallKositCommand;
use Moox\KositValidator\Commands\ValidateCommand;
use Moox\KositValidator\Models\KositValidation;
use Moox\KositValidator\Resources\KositValidationResource;
use Moox\KositValidator\Services\KositService;
use Spatie\LaravelPackageTools\Package;

class KositValidatorServiceProvider extends MooxServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        MorphPivotRelationRegistry::registerRelatedModel(KositValidation::class, [
            'display_columns' => ['input_path', 'passed', 'validated_at'],
            'translation_prefix' => 'kosit-validator::fields',
            'related_resource' => KositValidationResource::class,
            'record_select_label' => 'filenameLabel',
            'record_select_search_columns' => ['input_path'],
        ]);
    }

    public function configureMoox(Package $package): void
    {
        $package
            ->name('kosit-validator')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasRoutes('web')
            ->hasViews()
            ->hasCommands([InstallKositCommand::class, ValidateCommand::class, DoctorCommand::class])
            ->hasMigrations([
                'create_kosit_validations_table',
                'create_kosit_validatables_table',
            ]);

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
