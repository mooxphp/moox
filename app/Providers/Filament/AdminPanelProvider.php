<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Moox\Jobs\JobsPlugin;
use Moox\Page\PagePlugin;
use Moox\Sync\SyncPlugin;
use Moox\User\UserPlugin;
use Filament\PanelProvider;
use Moox\Builder\BuilderPlugin;
use Moox\Jobs\JobsFailedPlugin;
use Moox\Jobs\JobsBatchesPlugin;
use Moox\Jobs\JobsWaitingPlugin;
use Filament\Support\Colors\Color;
use Filament\Http\Middleware\Authenticate;
use Awcodes\FilamentGravatar\GravatarPlugin;
use Awcodes\FilamentGravatar\GravatarProvider;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ->defaultAvatarProvider(GravatarProvider::class)
            ->default()
            ->id('moox')
            ->path('moox')
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
                BuilderPlugin::make(),
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

                \Moox\LoginLink\LoginLinkPlugin::make(),

                \Moox\UserSession\UserSessionPlugin::make(),

                \Moox\Passkey\PasskeyPlugin::make(),
                \Moox\Notification\NotificationPlugin::make(),

            ]);
    }
}
