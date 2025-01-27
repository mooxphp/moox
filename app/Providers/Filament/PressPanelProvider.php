<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Moox\Media\MediaPlugin;
use Moox\Press\Services\Login;
use Filament\Support\Colors\Color;
use Moox\Security\Services\ResetPassword;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Moox\Security\Services\RequestPasswordReset;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Moox\Audit\AuditPlugin;
use Moox\Expiry\ExpiryPlugin;
use Moox\Jobs\JobsBatchesPlugin;
use Moox\Jobs\JobsFailedPlugin;
use Moox\Jobs\JobsPlugin;
use Moox\Jobs\JobsWaitingPlugin;
use Moox\LoginLink\LoginLinkPlugin;
use Moox\Notification\NotificationPlugin;
use Moox\Passkey\PasskeyPlugin;
use Moox\Press\Services\Login;
use Moox\Press\WpCategoryPlugin;
use Moox\Press\WpCommentMetaPlugin;
use Moox\Press\WpCommentPlugin;
use Moox\Press\WpMediaPlugin;
use Moox\Press\WpOptionPlugin;
use Moox\Press\WpPagePlugin;
use Moox\Press\WpPostMetaPlugin;
use Moox\Press\WpPostPlugin;
use Moox\Press\WpTagPlugin;
use Moox\Press\WpTermMetaPlugin;
use Moox\Press\WpTermPlugin;
use Moox\Press\WpTermRelationshipPlugin;
use Moox\Press\WpTermTaxonomyPlugin;
use Moox\Press\WpUserMetaPlugin;
use Moox\Press\WpUserPlugin;
use Moox\PressTrainings\WpTrainingPlugin;
use Moox\PressTrainings\WpTrainingsTopicPlugin;
use Moox\PressWiki\WpWikiCompanyTopicPlugin;
use Moox\PressWiki\WpWikiDepartmentTopicPlugin;
use Moox\PressWiki\WpWikiLetterTopicPlugin;
use Moox\PressWiki\WpWikiLocationTopicPlugin;
use Moox\PressWiki\WpWikiPlugin;
use Moox\PressWiki\WpWikiTopicPlugin;
use Moox\Security\ResetPasswordPlugin;
use Moox\Security\Services\RequestPasswordReset;
use Moox\Security\Services\ResetPassword;
use Moox\Training\TrainingDatePlugin;
use Moox\Training\TrainingInvitationPlugin;
use Moox\Training\TrainingPlugin;
use Moox\Training\TrainingTypePlugin;
use Moox\UserDevice\UserDevicePlugin;
use Moox\UserSession\UserSessionPlugin;

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
                Dashboard::class,
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
                WpPostPlugin::make(),
                WpPagePlugin::make(),
                WpMediaPlugin::make(),
                WpCategoryPlugin::make(),
                WpTagPlugin::make(),
                WpCommentPlugin::make(),

                // Press system plugins
                WpOptionPlugin::make(),
                WpUserMetaPlugin::make(),
                WpPostMetaPlugin::make(),
                WpCommentMetaPlugin::make(),
                WpTermMetaPlugin::make(),
                WpTermPlugin::make(),
                WpTermTaxonomyPlugin::make(),
                WpTermRelationshipPlugin::make(),

                // Notification plugin
                NotificationPlugin::make(),

                // Audit plugin
                AuditPlugin::make(),

                // Jobs plugins
                JobsPlugin::make(),
                JobsWaitingPlugin::make(),
                JobsFailedPlugin::make(),
                JobsBatchesPlugin::make(),

                // Sync Plugins
                // \Moox\Sync\SyncPlugin::make(),
                // \Moox\Sync\PlatformPlugin::make(),

                // User plugins
                WpUserPlugin::make(),
                UserDevicePlugin::make(),
                LoginLinkPlugin::make(),
                UserSessionPlugin::make(),
                PasskeyPlugin::make(),
                ResetPasswordPlugin::make(),

                // Expiry plugin
                ExpiryPlugin::make(),

                // Training plugins
                TrainingPlugin::make(),
                TrainingInvitationPlugin::make(),
                TrainingDatePlugin::make(),
                TrainingTypePlugin::make(),

                // Musste kurz raus, sorry ;-)
                WpWikiPlugin::make(),
                WpWikiTopicPlugin::make(),
                WpWikiLetterTopicPlugin::make(),
                WpWikiCompanyTopicPlugin::make(),
                WpWikiDepartmentTopicPlugin::make(),
                WpWikiLocationTopicPlugin::make(),

                \Moox\PressTrainings\WpTrainingPlugin::make(),
                \Moox\PressTrainings\WpTrainingsTopicPlugin::make(),
                MediaPlugin::make(),
                WpTrainingPlugin::make(),
                WpTrainingsTopicPlugin::make(),

            ]);
    }
}
