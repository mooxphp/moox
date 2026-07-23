<?php

declare(strict_types=1);

namespace Moox\KositValidator\Tests;

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
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Livewire\LivewireServiceProvider;
use Moox\KositValidator\Plugins\KositValidatorPlugin;
use Pest\Livewire\InteractsWithLivewire;

/**
 * Opt-in harness for tests that boot the Filament admin panel. Extend this
 * instead of {@see TestCase} when a test needs the panel context (e.g. it
 * asserts on Resource pages that resolve through the current panel).
 */
class FilamentTestCase extends TestCase
{
    use InteractsWithLivewire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);
        Filament::bootCurrentPanel();

        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag);
        $this->app['view']->share('errors', $errors);

        if ($this->app->bound('session')) {
            $this->app['session']->put('errors', $errors);
        }
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeGoogleMaterialDesignIconsServiceProvider::class,
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
            TablesServiceProvider::class,
            NotificationsServiceProvider::class,
            ActionsServiceProvider::class,
            InfolistsServiceProvider::class,
            WidgetsServiceProvider::class,
            SupportServiceProvider::class,
        ]);
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('core.use_google_icons', true);

        $viewErrorBag = new ViewErrorBag;
        $viewErrorBag->put('default', new MessageBag);
        $app['view']->share('errors', $viewErrorBag);

        $this->setUpFilamentPanel();
    }

    protected function setUpFilamentPanel(): void
    {
        $panel = Panel::make()
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Violet,
                'secondary' => Color::Neutral,
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
                KositValidatorPlugin::make(),
            ]);

        Filament::registerPanel($panel);
    }
}
