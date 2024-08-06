<?php

declare(strict_types=1);

namespace Moox\Audit;

use Moox\Core\Traits\TranslatableConfig;
use Moox\Audit\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AuditServiceProvider extends PackageServiceProvider
{
    use TranslatableConfig;

    public $name = 'audit';

    public function configurePackage(Package $package): void
    {
        $package
            ->name($this->name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['create_activity_log_table'])
            ->hasCommand(InstallCommand::class);
    }

    public function boot()
    {
        parent::boot();

        $this->translateConfigurations();

    }

    protected function translateConfigurations()
    {
        $translatedConfig = $this->translateConfig(config($this->name));
        config([$this->name => $translatedConfig]);
    }
}
