<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->authPasswordBroker('wpusers')
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
                // \Moox\Expiry\Widgets\MyExpiry::class,
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
                // Press plugins
                \Moox\Press\WpPostPlugin::make(),
                \Moox\Press\WpPagePlugin::make(),
                \Moox\Press\WpMediaPlugin::make(),
                \Moox\Press\WpCategoryPlugin::make(),
                \Moox\Press\WpTagPlugin::make(),
                \Moox\Press\WpCommentPlugin::make(),

                // Press system plugins
                \Moox\Press\WpOptionPlugin::make(),
                \Moox\Press\WpUserMetaPlugin::make(),
                \Moox\Press\WpPostMetaPlugin::make(),
                \Moox\Press\WpCommentMetaPlugin::make(),
                \Moox\Press\WpTermMetaPlugin::make(),
                \Moox\Press\WpTermPlugin::make(),
                \Moox\Press\WpTermTaxonomyPlugin::make(),
                \Moox\Press\WpTermRelationshipPlugin::make(),

                // Press custom plugins - should be moved to separate packages
                \Moox\Press\WpSchulungPlugin::make(),
                \Moox\Press\WpRubrikPlugin::make(),

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
                \Moox\Press\WpUserPlugin::make(),
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

                // Wiki Plugin
                \Moox\MooxPressWiki\MooxPressWikiPlugin::make(),

            ]);
    }
}
