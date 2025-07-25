<?php

namespace Moox\Press\Panels;

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
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PressPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('press')
            ->path('press')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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
            ])    ->plugins([
        \Moox\Press\WpCategoryPlugin::make(),
        \Moox\Press\WpCommentMetaPlugin::make(),
        \Moox\Press\WpCommentPlugin::make(),
        \Moox\Press\WpMediaPlugin::make(),
        \Moox\Press\WpOptionPlugin::make(),
        \Moox\Press\WpPagePlugin::make(),
        \Moox\Press\WpPostPlugin::make(),
        \Moox\Press\WpPostMetaPlugin::make(),
        \Moox\Press\WpTagPlugin::make(),
        \Moox\Press\WpTermMetaPlugin::make(),
        \Moox\Press\WpTermPlugin::make(),
        \Moox\Press\WpTermRelationshipPlugin::make(),
        \Moox\Press\WpTermTaxonomyPlugin::make(),
        \Moox\Press\WpUserMetaPlugin::make(),
        \Moox\Press\WpUserPlugin::make()
    ]);
    }
}
