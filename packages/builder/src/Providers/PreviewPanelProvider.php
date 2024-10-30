<?php

declare(strict_types=1);

namespace Moox\Builder\Providers;

use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PreviewPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('preview')
            ->path('preview')
            ->brandName('Moox Builder Preview')
            ->brandLogo(asset('img/logo.png'))
            ->brandLogoHeight('1.6rem')
            ->colors([
                'primary' => Color::Indigo,
                'secondary' => Color::Neutral,
            ])
            //->discoverResources(in: app_path('Preview/Resources'), for: 'App\\Preview\\Resources')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([
                Dashboard::class,
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
