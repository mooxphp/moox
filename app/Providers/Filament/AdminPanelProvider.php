<?php

namespace App\Providers\Filament;

use Awcodes\FilamentGravatar\GravatarPlugin;
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
use Moox\Security\Services\RequestPasswordReset;
use Moox\User\Services\Login;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('moox')
            ->path('moox')
            ->passwordReset(RequestPasswordReset::class)
            ->login(Login::class)
            // TODO: check this
            //->authGuard('admin')
            ->brandLogo(asset('img/logo.png'))
            ->brandLogoHeight('1.6rem')
            ->colors([
                'primary' => Color::Indigo,
                'secondary' => Color::Neutral,
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

                // Development
                // GravatarPlugin::make(),
                \Moox\Category\CategoryPlugin::make(),
                \Moox\Tag\TagPlugin::make(),
                \Moox\Sync\PlatformPlugin::make(),
                \Moox\Audit\AuditPlugin::make(),

                \Moox\Locate\AreaPlugin::make(),
                //\Moox\Locate\CountryPlugin::make(),
                //\Moox\Locate\LanguagePlugin::make(),
                //\Moox\Locate\TimezonePlugin::make(),

                \Moox\UserDevice\UserDevicePlugin::make(),
                // CMS plugin
                \Moox\Page\PagePlugin::make(),

                // Notification plugin
                \Moox\Notification\NotificationPlugin::make(),

                // Audit plugin
                \Moox\Audit\AuditPlugin::make(),

                // Jobs plugins
                \Moox\Jobs\JobsPlugin::make(),
                \Moox\Jobs\JobsWaitingPlugin::make(),
                \Moox\Jobs\JobsFailedPlugin::make(),
                \Moox\Jobs\JobsBatchesPlugin::make(),

                // Sync Plugins
                \Moox\Sync\SyncPlugin::make(),
                \Moox\Sync\PlatformPlugin::make(),

                // User plugins
                \Moox\User\UserPlugin::make(),
                GravatarPlugin::make(),

                \Moox\UserDevice\UserDevicePlugin::make(),
                \Moox\LoginLink\LoginLinkPlugin::make(),
                \Moox\UserSession\UserSessionPlugin::make(),
                \Moox\Passkey\PasskeyPlugin::make(),
                \Moox\Security\ResetPasswordPlugin::make(),

                // Expiry plugin
                \Moox\Expiry\ExpiryPlugin::make(),

                // Training plugins
                \Moox\Training\TrainingPlugin::make(),
                \Moox\Training\TrainingInvitationPlugin::make(),
                \Moox\Training\TrainingDatePlugin::make(),
                \Moox\Training\TrainingTypePlugin::make(),

                // Builder plugin
                \Moox\Builder\ItemPlugin::make(),

            ]);
    }
}
