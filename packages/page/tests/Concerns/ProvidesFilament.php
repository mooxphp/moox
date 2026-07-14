<?php

declare(strict_types=1);

namespace Moox\Page\Tests\Concerns;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Codeat3\BladeGoogleMaterialDesignIcons\BladeGoogleMaterialDesignIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\Colors\Color;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Cookie\CookieServiceProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Encryption\EncryptionServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Pagination\PaginationServiceProvider;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionServiceProvider;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\ViewServiceProvider;
use Livewire\LivewireServiceProvider;
use Moox\Page\Plugins\PagePlugin;
use Orchestra\Testbench\Attributes\WithMigration;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

final class ProvidesFilament
{
    /**
     * @return list<class-string>
     */
    public static function providers(): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeGoogleMaterialDesignIconsServiceProvider::class,
            ActionsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            AuthServiceProvider::class,
            CookieServiceProvider::class,
            DatabaseServiceProvider::class,
            EncryptionServiceProvider::class,
            FilesystemServiceProvider::class,
            PaginationServiceProvider::class,
            SessionServiceProvider::class,
            TranslationServiceProvider::class,
            ValidationServiceProvider::class,
            ViewServiceProvider::class,
            LivewireServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
        ];
    }

  public static function panel(): Panel
    {
        return Panel::make()
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Violet,
            ])
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
                AuthenticateSession::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                PagePlugin::make(),
            ]);
    }
}
