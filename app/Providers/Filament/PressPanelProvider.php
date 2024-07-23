<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;

use Moox\Security\Services\Login;
use Filament\Support\Colors\Color;
use Moox\Security\Services\ResetPassword;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Moox\Security\Services\RequestPasswordReset;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class PressPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('press')
            ->path('press')
            ->passwordReset(RequestPasswordReset::class, ResetPassword::class)
            ->login(Login::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Press/Resources'), for: 'App\\Filament\\Press\\Resources')
            ->discoverPages(in: app_path('Filament/Press/Pages'), for: 'App\\Filament\\Press\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Press/Widgets'), for: 'App\\Filament\\Press\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ->plugins([
                \Moox\Press\WpPostPlugin::make(),
                \Moox\Press\WpPagePlugin::make(),
                \Moox\Press\WpMediaPlugin::make(),
                \Moox\Press\WpCategoryPlugin::make(),
                \Moox\Press\WpTagPlugin::make(),
                \Moox\Press\WpUserPlugin::make(),
                \Moox\Press\WpOptionPlugin::make(),

            ]);
    }
}
