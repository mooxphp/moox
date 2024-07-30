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
use Moox\Jobs\JobsBatchesPlugin;
use Moox\Jobs\JobsFailedPlugin;
use Moox\Jobs\JobsPlugin;
use Moox\Jobs\JobsWaitingPlugin;
use Moox\Page\PagePlugin;
use Moox\Security\ResetPasswordPlugin;
use Moox\Security\SecurityPlugin;
use Moox\Security\Services\RequestPasswordReset;
use Moox\Sync\SyncPlugin;
use Moox\User\UserPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ->defaultAvatarProvider(GravatarProvider::class)
            ->default()
            ->id('moox')
            ->path('moox')
            ->passwordReset(RequestPasswordReset::class)
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
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
            ->spa()
            ->plugins([
                // GravatarPlugin::make(),
                JobsPlugin::make(),
                JobsWaitingPlugin::make(),
                JobsFailedPlugin::make(),
                JobsBatchesPlugin::make(),
                JobsPlugin::make(),
                PagePlugin::make(),
                SyncPlugin::make(),
                UserPlugin::make(),
                \Moox\Sync\PlatformPlugin::make(),
                \Moox\Audit\AuditPlugin::make(),

                \Moox\UserDevice\UserDevicePlugin::make(),

                \Moox\UserSession\UserSessionPlugin::make(),

                \Moox\Passkey\PasskeyPlugin::make(),

                \Moox\Notification\NotificationPlugin::make(),

                SecurityPlugin::make(),

                ResetPasswordPlugin::make(),

                \Moox\Builder\BuilderPlugin::make(),

            
                \Moox\LoginLink\LoginLinkPlugin::make(),

            ]);
    }
}
