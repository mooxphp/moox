<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Providers;

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Livewire\Livewire;
use Moox\DataLanguages\Http\Middleware\LanguageMiddleware;
use Moox\Page\PagePlugin;
use Symfony\Component\Translation\LocaleSwitcher;

class LanguagePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        Livewire::component('locale-switcher', LocaleSwitcher::class);

        return $panel
            ->id('language')
            ->path('language')
            ->brandName('MooxLanguage')
            ->brandLogo(asset('img/logo.png'))
            ->brandLogoHeight('1.6rem')
            ->colors([
                'primary' => Color::Indigo,
                'secondary' => Color::Neutral,
            ])
            ->discoverResources(in: __DIR__.'/../Resources', for: 'Moox\\DataLanguages\\Resources')
            ->plugins([])
            ->pages([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                LanguageMiddleware::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
            ])
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label(app()->getLocale())
                    ->url('')
                    ->icon('heroicon-o-cog-6-tooth'),
                // ...
            ])
            ->maxContentWidth('full')
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                \Filament\View\PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => \Illuminate\Support\Facades\Blade::render('@livewire(\'language-switch\',[\'context\'=>\'backend\'])'),
            )->plugins([
                PagePlugin::make(),
            ]);
    }

    // public function boot()
    // {
    //     Filament::serving(function () {
    //         // Dynamisch das Locale setzen
    //         app()->setLocale(session('locale', app()->getLocale()));
    //     });
    // }
}
