<?php

namespace App\Providers\Filament;

use Awcodes\FilamentGravatar\GravatarPlugin;
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
use Moox\Audit\AuditPlugin;
use Moox\Builder\FullItemPlugin;
use Moox\Builder\ItemPlugin;
use Moox\Builder\NestedTaxonomyPlugin;
use Moox\Builder\SimpleItemPlugin;
use Moox\Builder\SimpleTaxonomyPlugin;
use Moox\Category\CategoryPlugin;
use Moox\Expiry\ExpiryPlugin;
use Moox\Jobs\JobsBatchesPlugin;
use Moox\Jobs\JobsFailedPlugin;
use Moox\Jobs\JobsPlugin;
use Moox\Jobs\JobsWaitingPlugin;
use Moox\Locate\AreaPlugin;
use Moox\LoginLink\LoginLinkPlugin;
use Moox\Notification\NotificationPlugin;
use Moox\Page\PagePlugin;
use Moox\Passkey\PasskeyPlugin;
use Moox\Security\ResetPasswordPlugin;
use Moox\Security\Services\RequestPasswordReset;
use Moox\Tag\TagPlugin;
use Moox\Training\TrainingDatePlugin;
use Moox\Training\TrainingInvitationPlugin;
use Moox\Training\TrainingPlugin;
use Moox\Training\TrainingTypePlugin;
use Moox\User\Services\Login;
use Moox\User\UserPlugin;
use Moox\UserDevice\UserDevicePlugin;
use Moox\UserSession\UserSessionPlugin;

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
            // ->authGuard('admin')
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

                // Development
                // GravatarPlugin::make(), <- Remove!
                AuditPlugin::make(),
                // AreaPlugin::make(), <- Remove!
                // \Moox\Locate\CountryPlugin::make(), <- Remove!
                // \Moox\Locate\LanguagePlugin::make(),
                // \Moox\Locate\TimezonePlugin::make(),

                // Builder plugin - missing config, will be removed completely
                // SimpleTaxonomyPlugin::make(),
                // NestedTaxonomyPlugin::make(),
                // ItemPlugin::make(),
                // FullItemPlugin::make(),
                // SimpleItemPlugin::make(),

                // CMS plugin
                PagePlugin::make(),
                CategoryPlugin::make(),
                TagPlugin::make(),

                // Notification plugin
                NotificationPlugin::make(),

                // Jobs plugins
                JobsPlugin::make(),
                JobsWaitingPlugin::make(),
                JobsFailedPlugin::make(),
                JobsBatchesPlugin::make(),

                // Sync Plugins
                // \Moox\Sync\SyncPlugin::make(),
                // \Moox\Sync\PlatformPlugin::make(),

                // User plugins
                UserPlugin::make(),
                UserDevicePlugin::make(),
                LoginLinkPlugin::make(),
                UserSessionPlugin::make(),
                PasskeyPlugin::make(),
                ResetPasswordPlugin::make(),

                // Expiry plugin
                ExpiryPlugin::make(),

                // Training plugins
                \Moox\Training\TrainingPlugin::make(),
                \Moox\Training\TrainingInvitationPlugin::make(),
                \Moox\Training\TrainingDatePlugin::make(),
                \Moox\Training\TrainingTypePlugin::make(),

                // Builder plugin
                \Moox\Builder\ItemPlugin::make(),
                \Moox\Media\MediaPlugin::make(),

                TrainingPlugin::make(),
                TrainingInvitationPlugin::make(),
                TrainingDatePlugin::make(),
                TrainingTypePlugin::make(),
            ]);
    }
}
