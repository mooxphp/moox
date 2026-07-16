<?php

declare(strict_types=1);

namespace Moox\Static\Tests;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\PaginationServiceProvider;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionServiceProvider;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\ViewServiceProvider;
use Livewire\LivewireServiceProvider;
use Moox\Core\CoreServiceProvider;
use Moox\DevTools\Models\TestUser;
use Moox\Localization\LocalizationServiceProvider;
use Moox\Static\Plugins\StaticPlugin;
use Moox\Static\StaticServiceProvider;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;

#[WithMigration('laravel', 'cache', 'queue')]
class TestCase extends Orchestra
{
    use RefreshDatabase;

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

    protected function defineDatabaseMigrations(): void
    {
        $this->loadPackageMigrations();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('static.readonly', false);
        $app['config']->set('translatable.locales', ['en_US', 'de_DE']);
        $app['config']->set('translatable.fallback_locale', 'en_US');
        $app['config']->set('translatable.use_fallback', true);

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
                StaticPlugin::make(),
            ]);

        Filament::registerPanel($panel);
    }

    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeGoogleMaterialDesignIconsServiceProvider::class,
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            AuthServiceProvider::class,
            CookieServiceProvider::class,
            DatabaseServiceProvider::class,
            EncryptionServiceProvider::class,
            SessionServiceProvider::class,
            ValidationServiceProvider::class,
            ViewServiceProvider::class,
            PaginationServiceProvider::class,
            TranslationServiceProvider::class,
            FilesystemServiceProvider::class,
            SupportServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
            TablesServiceProvider::class,
            NotificationsServiceProvider::class,
            ActionsServiceProvider::class,
            InfolistsServiceProvider::class,
            WidgetsServiceProvider::class,
            CoreServiceProvider::class,
            LocalizationServiceProvider::class,
            StaticServiceProvider::class,
        ];
    }

    protected function loadPackageMigrations(): void
    {
        foreach (['create_static_entries_table', 'create_static_entry_translations_table'] as $migration) {
            $path = dirname(__DIR__).'/database/migrations/'.$migration.'.php.stub';

            if (! is_file($path)) {
                continue;
            }

            $instance = include $path;
            $instance->up();
        }
    }

    protected function createTestUser(): TestUser
    {
        return TestUser::query()->create([
            'name' => 'Test User',
            'email' => 'test-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}
