<?php

declare(strict_types=1);

namespace Moox\VeraPdf;

use Moox\Core\MooxServiceProvider;
use Moox\Core\Support\MorphPivot\MorphPivotRelationRegistry;
use Moox\VeraPdf\Commands\DoctorCommand;
use Moox\VeraPdf\Commands\InstallVeraPdfCommand;
use Moox\VeraPdf\Commands\ValidateCommand;
use Moox\VeraPdf\Models\VeraPdfValidation;
use Moox\VeraPdf\Services\VeraPdfService;
use Spatie\LaravelPackageTools\Package;

class VeraPdfServiceProvider extends MooxServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        MorphPivotRelationRegistry::registerRelatedModel(VeraPdfValidation::class, [
            'display_columns' => ['input_path', 'passed', 'validated_at'],
            'translation_prefix' => 'verapdf::fields',
            'record_select_label' => 'filenameLabel',
            'record_select_search_columns' => ['input_path'],
        ]);
    }

    public function configureMoox(Package $package): void
    {
        $package
            ->name('verapdf')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasCommands([InstallVeraPdfCommand::class, ValidateCommand::class, DoctorCommand::class])
            ->hasMigrations([
                'create_verapdf_validations_table',
                'create_verapdf_validatables_table',
            ]);

        $this->getMooxPackage()
            ->title('Moox VeraPdf')
            ->released(false)
            ->stability('dev')
            ->category('development')
            ->usedFor([
                'veraPDF CLI wrapper for PDF/A-3 validation and audit persistence',
            ]);
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(VeraPdfService::class);
    }
}
