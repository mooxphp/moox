<?php

declare(strict_types=1);

namespace Moox\PressTrainings;

use Moox\Core\MooxServiceProvider;
use Moox\PressTrainings\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;

class PressTrainingServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('press-trainings')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasCommand(InstallCommand::class);
    }
}
