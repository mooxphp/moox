<?php

namespace Moox\Localization\Filament\Providers;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class LocalizationPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('localization')
            ->path('localization')
            ->brandName('MooxLocalization')
            ->login()
            ->colors([
                'primary' => Color::Indigo,
                'secondary' => Color::Neutral,
            ])
            ->discoverResources(in: __DIR__.'/../Resources', for: 'Moox\\Localization\\Filament\\Resources')
            ->discoverPages(in: __DIR__.'/../Pages', for: 'Moox\\Localization\\Filament\\Pages')
            ->discoverWidgets(in: __DIR__.'/../Widgets', for: 'Moox\\Localization\\Filament\\Widgets')
            ->pages([
                Dashboard::class,
            ])->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('@livewire(\'language-switch\',[\'context\'=>\'backend\'])'),
            )
            ->plugins([
            ]);
    }
}
