<?php

namespace App\Providers\Filament;

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
use Moox\BackupServerUi\BackupLogItemPlugin;
use Moox\BackupServerUi\BackupPlugin;
use Moox\BackupServerUi\DestinationPlugin;
use Moox\BackupServerUi\SourcePlugin;
use Moox\Devops\MooxProjectPlugin;
use Moox\Devops\MooxServerPlugin;
use Moox\Jobs\JobsBatchesPlugin;
use Moox\Jobs\JobsFailedPlugin;
use Moox\Jobs\JobsPlugin;
use Moox\Jobs\JobsWaitingPlugin;
use Moox\LoginLink\LoginLinkPlugin;
use Moox\Passkey\PasskeyPlugin;
use Moox\Restore\RestoreBackupPlugin;
use Moox\Restore\RestoreDestinationPlugin;
use Moox\Security\ResetPasswordPlugin;
use Moox\Security\Services\RequestPasswordReset;
use Moox\User\Services\Login;
use Moox\User\UserPlugin;
use Moox\UserDevice\UserDevicePlugin;
use Moox\UserSession\UserSessionPlugin;

class DevopsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('devops')
            ->path('devops')
            ->passwordReset(RequestPasswordReset::class)
            ->login(Login::class)
            ->brandLogo(asset('img/logo.png'))
            ->brandLogoHeight('1.6rem')
            ->colors([
                'primary' => Color::Indigo,
                'secondary' => Color::Neutral,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
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
            ->spa()
            ->plugins([

                MooxProjectPlugin::make(),
                MooxServerPlugin::make(),

                BackupPlugin::make(),
                DestinationPlugin::make(),
                SourcePlugin::make(),
                BackupLogItemPlugin::make(),

                RestoreBackupPlugin::make(),
                RestoreDestinationPlugin::make(),

                JobsPlugin::make(),
                JobsWaitingPlugin::make(),
                JobsFailedPlugin::make(),
                JobsBatchesPlugin::make(),

                UserPlugin::make(),
                UserDevicePlugin::make(),
                LoginLinkPlugin::make(),
                UserSessionPlugin::make(),
                PasskeyPlugin::make(),
                ResetPasswordPlugin::make(),

            ]);
    }
}
