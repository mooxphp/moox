<?php

declare(strict_types=1);

namespace Moox\Builder\Providers;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Moox\Builder\Pages\BuilderDashboard;
use Moox\Expiry\ExpiryPlugin;

class BuilderPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('builder')
            ->path('builder')
            ->brandName('MooxBuilder')
            ->brandLogo(asset('img/logo.png'))
            ->brandLogoHeight('1.6rem')
            ->colors([
                'primary' => Color::Indigo,
                'secondary' => Color::Neutral,
            ])
            //->discoverResources(in: app_path('Builder/Resources'), for: 'App\\Builder\\Resources')
            ->plugins([
                ExpiryPlugin::make(),
            ])
            ->pages([
                BuilderDashboard::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
            ])
            ->maxContentWidth('full')
            ->sidebarCollapsibleOnDesktop();
    }
}
