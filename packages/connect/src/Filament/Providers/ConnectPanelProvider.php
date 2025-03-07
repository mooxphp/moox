<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Providers;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Moox\Localization\Http\Middleware\LanguageMiddleware;

final class ConnectPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('connect')
            ->path('connect')
            ->brandName('Moox Connect')
            ->login()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                // LanguageMiddleware::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->discoverResources(in: __DIR__.'/../Resources', for: 'Moox\\Connect\\Filament\\Resources')
            ->discoverPages(in: __DIR__.'/../Pages', for: 'Moox\\Connect\\Filament\\Pages')
            ->discoverWidgets(in: __DIR__.'/../Widgets', for: 'Moox\\Connect\\Filament\\Widgets')
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                // \Moox\Connect\Filament\Plugins\ApiConnectionPlugin::make(),
                // ApiConnectionPlugin::make(),
                // ApiEndpointPlugin::make(),
                // ApiLogPlugin::make(),
            ])
            ->authMiddleware(['auth']);
    }
}
