<?php

declare(strict_types=1);

namespace Moox\Training;

use Moox\Training\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TrainingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('trainings')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                'create_training_dateables_table',
                'create_training_dates_table',
                'create_training_invitationables_table',
                'create_training_invitations_table',
                'create_training_types_table',
                'create_trainingables_table',
                'create_trainings_table',
                'foreigns_for_training_dateables_table',
                'foreigns_for_training_dates_table',
                'foreigns_for_training_invitationables_table',
                'foreigns_for_training_invitations_table',
                'foreigns_for_trainingables_table',
                'foreigns_for_trainings_table',
            ])
            ->hasCommand(InstallCommand::class);
    }
}
