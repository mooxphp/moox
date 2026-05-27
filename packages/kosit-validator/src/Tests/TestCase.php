<?php

declare(strict_types=1);

namespace Moox\KositValidator\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Codeat3\BladeGoogleMaterialDesignIcons\BladeGoogleMaterialDesignIconsServiceProvider;
use Composer\Autoload\ClassLoader;
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
use Illuminate\Database\Eloquent\Factories\Factory;
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
use Moox\KositValidator\KositValidatorServiceProvider;
use Moox\KositValidator\Moox\Plugins\KositValidatorPlugin;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;
use Pest\Livewire\InteractsWithLivewire;
use Tests\TestCase as ApplicationTestCase;

(static function (): void {
    foreach (spl_autoload_functions() ?: [] as $autoloader) {
        if (! is_array($autoloader)) {
            continue;
        }

        $loader = $autoloader[0] ?? null;

        if ($loader instanceof ClassLoader) {
            $loader->addPsr4('Moox\\KositValidator\\Tests\\', dirname(__DIR__, 2).'/tests');

            return;
        }
    }
})();

if (class_exists(Orchestra::class)) {
    #[WithMigration('laravel')]
    #[WithMigration('session')]
    class TestCase extends Orchestra
    {
        use InteractsWithLivewire;
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

            Factory::guessFactoryNamesUsing(
                fn (string $modelName): string => 'Moox\\KositValidator\\Database\\Factories\\'.class_basename($modelName).'Factory'
            );
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
                KositValidatorServiceProvider::class,
            ];
        }

        protected function getEnvironmentSetUp($app): void
        {
            $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
            $app['config']->set('database.default', 'testing');
            $app['config']->set('session.driver', 'array');
            $app['config']->set('auth.providers.users.model', TestUser::class);
            $app['config']->set('auth.guards.web.provider', 'users');
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

        protected function defineDatabaseMigrations(): void
        {
            $migration = include dirname(__DIR__, 2).'/database/migrations/create_kosit_validations_table.php.stub';

            $migration->up();
        }
    }
} else {
    class TestCase extends ApplicationTestCase
    {
        use RefreshDatabase;

        protected function setUp(): void
        {
            parent::setUp();

            config()->set('auth.providers.users.model', TestUser::class);
        }
    }
}
