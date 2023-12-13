<?php

namespace Moox\User;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Moox\User\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UserServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('user')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommand(InstallCommand::class);
    }

    public function boot()
    {
        parent::boot();

        Filament::serving(function () {
            Filament::registerNavigationGroups([
                NavigationGroup::make()
                    ->label('User'),
            ]);

            Filament::registerNavigationItems([
                NavigationItem::make('Profile')
                    ->url('/moox/profile')
                    ->icon('heroicon-o-user')
                    ->group('User'),
            ]);
        });
    }
}
