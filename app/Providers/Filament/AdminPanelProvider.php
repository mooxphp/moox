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
use Moox\Audit\AuditPlugin;
use Moox\Category\CategoryPlugin;
use Moox\Data\Filament\Plugins\StaticCountryPlugin;
use Moox\Data\Filament\Plugins\StaticCurrencyPlugin;
use Moox\Data\Filament\Plugins\StaticLanguagePlugin;
use Moox\Data\Filament\Plugins\StaticLocalePlugin;
use Moox\Data\Filament\Plugins\StaticTimezonePlugin;
use Moox\Draft\Moox\Plugins\DraftPlugin;
use Moox\Expiry\ExpiryPlugin;
use Moox\Item\Moox\Plugins\ItemPlugin;
use Moox\Jobs\JobsBatchesPlugin;
use Moox\Jobs\JobsFailedPlugin;
use Moox\Jobs\JobsPlugin;
use Moox\Jobs\JobsWaitingPlugin;
use Moox\Localization\Filament\Plugins\LocalizationPlugin;
use Moox\LoginLink\LoginLinkPlugin;
use Moox\Media\MediaPlugin;
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

                DraftPlugin::make(),
                ItemPlugin::make(),
                AuditPlugin::make(),

                PagePlugin::make(),
                CategoryPlugin::make(),
                TagPlugin::make(),

                NotificationPlugin::make(),

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

                ExpiryPlugin::make(),
                MediaPlugin::make(),

                TrainingPlugin::make(),
                TrainingInvitationPlugin::make(),
                TrainingDatePlugin::make(),
                TrainingTypePlugin::make(),

                LocalizationPlugin::make(),
                StaticLocalePlugin::make(),
                StaticCountryPlugin::make(),
                StaticLanguagePlugin::make(),
                StaticTimezonePlugin::make(),
                StaticCurrencyPlugin::make(),
            ]);
    }
}
