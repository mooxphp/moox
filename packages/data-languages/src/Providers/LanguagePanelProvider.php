<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Providers;

use Filament\Panel;
use Livewire\Livewire;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Symfony\Component\Translation\LocaleSwitcher;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

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
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
            ])
            ->maxContentWidth('full')
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                \Filament\View\PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => \Illuminate\Support\Facades\Blade::render('@livewire(\'language-switch\',[\'context\'=>\'backend\'])'),
            );
    }
}
