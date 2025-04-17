<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Moox\Tag\TagPlugin;
use Moox\Jobs\JobsPlugin;
use Moox\Page\PagePlugin;
use Moox\User\UserPlugin;
use Filament\PanelProvider;
use Moox\Audit\AuditPlugin;
use Moox\Media\MediaPlugin;
use Filament\Pages\Dashboard;
use Moox\Expiry\ExpiryPlugin;
use Moox\User\Services\Login;
use Moox\Jobs\JobsFailedPlugin;
use Moox\Passkey\PasskeyPlugin;
use Moox\Jobs\JobsBatchesPlugin;
use Moox\Jobs\JobsWaitingPlugin;
use Moox\Training\TrainingPlugin;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Moox\LoginLink\LoginLinkPlugin;
use Moox\Training\TrainingDatePlugin;
use Moox\Training\TrainingTypePlugin;
use Moox\UserDevice\UserDevicePlugin;
use Moox\Item\Moox\Plugins\ItemPlugin;
use Moox\Security\ResetPasswordPlugin;
use Moox\UserSession\UserSessionPlugin;
use Filament\Widgets\FilamentInfoWidget;
use Moox\Draft\Moox\Plugins\DraftPlugin;
use Moox\Notification\NotificationPlugin;
use Filament\Http\Middleware\Authenticate;
use Moox\Training\TrainingInvitationPlugin;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Moox\Security\Services\RequestPasswordReset;
use Moox\Data\Filament\Plugins\StaticLocalePlugin;
use Moox\Data\Filament\Plugins\StaticCountryPlugin;
use Moox\Data\Filament\Plugins\StaticCurrencyPlugin;
use Moox\Data\Filament\Plugins\StaticLanguagePlugin;
use Moox\Data\Filament\Plugins\StaticTimezonePlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Moox\Category\Moox\Entities\Categories\Plugins\CategoryPlugin;
use Moox\Localization\Filament\Plugins\LocalizationPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

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
            ->font('Exo 2')
            ->favicon(asset('img/moox-icon.png'))
            ->colors([
                'primary' => Color::Violet,
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

                // DEVELOPMENT
                // SoftDeleteItemPlugin::make(),

                // Main
                ExpiryPlugin::make(),
                NotificationPlugin::make(),

                // Items
                DraftPlugin::make(),
                ItemPlugin::make(),

                // CMS
                PagePlugin::make(),
                MediaPlugin::make(),
                CategoryPlugin::make(),
                TagPlugin::make(),

                // Jobs
                JobsPlugin::make(),
                JobsWaitingPlugin::make(),
                JobsFailedPlugin::make(),
                JobsBatchesPlugin::make(),

                // User
                UserPlugin::make(),
                UserDevicePlugin::make(),
                LoginLinkPlugin::make(),
                UserSessionPlugin::make(),
                PasskeyPlugin::make(),
                ResetPasswordPlugin::make(),

                // System
                LocalizationPlugin::make(),
                AuditPlugin::make(),

                // Training
                TrainingPlugin::make(),
                TrainingInvitationPlugin::make(),
                TrainingDatePlugin::make(),
                TrainingTypePlugin::make(),

                // Data
                StaticLocalePlugin::make(),
                StaticCountryPlugin::make(),
                StaticLanguagePlugin::make(),
                StaticTimezonePlugin::make(),
                StaticCurrencyPlugin::make(),
            ]);
    }
}
