<?php

namespace Moox\User;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Jeffgreco13\FilamentBreezy\BreezyCore as OriginalBreezyCore;

class CustomBreezyCore extends OriginalBreezyCore
{
        Filament::serving(function () {
            Filament::registerNavigationGroups([
                NavigationGroup::make()
                    ->label('User')
                    ->icon('heroicon-s-users'), // Replace with your desired icon
            ]);

            Filament::registerNavigationItems([
                NavigationItem::make('Profile')
                    ->url('/user/profile') // Replace with the actual profile URL
                    ->icon('heroicon-o-user') // Replace with your desired icon
                    ->group('User'),
            ]);
        });
}

/*
Needs to be registered in serviceprovider

     public function register()
    {
        $this->app->bind(OriginalBreezyCore::class, CustomBreezyCore::class);
    }
*/
