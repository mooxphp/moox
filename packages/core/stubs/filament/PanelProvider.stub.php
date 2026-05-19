<?php

namespace {{ namespace }};

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
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
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class {{ class }} extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            {{ default_panel }}->id('{{ panel_id }}')
            ->path('{{ panel_path }}')
            ->login()
            ->colors([
                'primary' => {{ primary_color }},
            ]);

        if (is_file(public_path('vendor/core/signet.svg'))) {
            $panel = $panel
                ->brandLogo(asset('vendor/core/signet.svg'))
                ->brandLogoHeight('3.5rem');
        }

        return $panel
            ->discoverResources(in: app_path('Filament/Resources'), for: '{{ app_namespace }}\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: '{{ app_namespace }}\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: '{{ app_namespace }}\Filament\Widgets')
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
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
            ]);
    }
}
