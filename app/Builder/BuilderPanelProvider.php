<?php

namespace App\Builder;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Moox\Security\Services\RequestPasswordReset;
use Moox\User\Services\Login;

class BuilderPanelProvider extends PanelProvider
{
    public function getTitle(): string
    {
        return 'Builder';
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('builder')
            ->path('builder')
            ->passwordReset(RequestPasswordReset::class)
            ->login(Login::class)
        // TODO: check this
        // ->authGuard('admin')
            ->brandLogo(asset('img/logo.png'))
            ->brandLogoHeight('1.6rem')
            ->font('Exo 2')
            ->favicon(asset('img/moox-icon.png'))
            ->colors([
                'primary' => Color::Violet,
                'secondary' => Color::Neutral,
            ])
            ->discoverResources(in: app_path('Builder/Resources'), for: 'App\\Builder\\Resources')
            ->discoverPages(in: app_path('Builder/Pages'), for: 'App\\Builder\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Builder/Widgets'), for: 'App\\Builder\\Widgets')
            ->widgets([
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
            ])
            ->spa();
    }
}
