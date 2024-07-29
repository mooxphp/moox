<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Moox\Security\Services\Login;
use Moox\Security\Services\RequestPasswordReset;
use Moox\Security\Services\ResetPassword;

class PressPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('press')
            ->path('press')
            ->authGuard('press')
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

                \Moox\Expiry\ExpiryPlugin::make(),

                \Moox\Training\TrainingPlugin::make(),
                \Moox\Training\TrainingInvitationPlugin::make(),
                \Moox\Training\TrainingDatePlugin::make(),
                \Moox\Training\TrainingTypePlugin::make(),

                \Moox\Security\SecurityPlugin::make(),
                \Moox\Security\ResetPasswordPlugin::make(),

                \Moox\Sync\PlatformPlugin::make(),
                \Moox\Sync\SyncPlugin::make(),

            ]);
    }
}
