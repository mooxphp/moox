<?php

declare(strict_types=1);

namespace Moox\Company\Tests;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Moox\Company\Plugins\CompanyPlugin;
use Moox\DevTools\Models\TestUser;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;
use Pest\Livewire\InteractsWithLivewire;

#[WithMigration('laravel', 'cache', 'queue')]
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
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadDependencyMigrations();
        $this->loadPackageMigrations();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('session.lottery', [100, 100]);
        $app['config']->set('company.taxonomies', []);
        $app['config']->set('company.readonly', false);

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
                CompanyPlugin::make(),
            ]);

        Filament::registerPanel($panel);
    }

    protected function getPackageProviders($app): array
    {
        return [
            \BladeUI\Icons\BladeIconsServiceProvider::class,
            \BladeUI\Heroicons\BladeHeroiconsServiceProvider::class,
            \Filament\Actions\ActionsServiceProvider::class,
            \Codeat3\BladeGoogleMaterialDesignIcons\BladeGoogleMaterialDesignIconsServiceProvider::class,
            \Filament\FilamentServiceProvider::class,
            \Filament\Forms\FormsServiceProvider::class,
            \Filament\Infolists\InfolistsServiceProvider::class,
            \Filament\Notifications\NotificationsServiceProvider::class,
            \Filament\Schemas\SchemasServiceProvider::class,
            \Filament\Support\SupportServiceProvider::class,
            \Filament\Tables\TablesServiceProvider::class,
            \Filament\Widgets\WidgetsServiceProvider::class,
            \Illuminate\Auth\AuthServiceProvider::class,
            \Illuminate\Cookie\CookieServiceProvider::class,
            \Illuminate\Database\DatabaseServiceProvider::class,
            \Illuminate\Encryption\EncryptionServiceProvider::class,
            \Illuminate\Filesystem\FilesystemServiceProvider::class,
            \Illuminate\Pagination\PaginationServiceProvider::class,
            \Illuminate\Session\SessionServiceProvider::class,
            \Illuminate\Translation\TranslationServiceProvider::class,
            \Illuminate\Validation\ValidationServiceProvider::class,
            \Illuminate\View\ViewServiceProvider::class,
            \Livewire\LivewireServiceProvider::class,
            \Moox\Company\CompanyServiceProvider::class,
            \Moox\Core\CoreServiceProvider::class,
            \RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider::class,
       
       
        ];
    }

    protected function loadDependencyMigrations(): void
    {
        // users/session tables come from #[WithMigration('laravel', …)] and #[WithMigration('session')]

        if (! Schema::hasTable('static_languages')) {
            (new class extends Migration
            {
                public function up(): void
                {
                    Schema::create('static_languages', function (Blueprint $table): void {
                        $table->id();
                        $table->string('alpha2', 2);
                        $table->string('common_name');
                        $table->timestamps();
                    });
                }
            })->up();
        }

        if (! Schema::hasTable('localizations')) {
            (new class extends Migration
            {
                public function up(): void
                {
                    Schema::create('localizations', function (Blueprint $table): void {
                        $table->id();
                        $table->foreignId('language_id')->constrained('static_languages')->cascadeOnDelete();
                        $table->string('title');
                        $table->string('slug')->unique();
                        $table->string('locale_variant');
                        $table->timestamps();
                    });
                }
            })->up();
        }

    }

    protected function loadPackageMigrations(): void
    {
        $path = dirname(__DIR__).'/database/migrations/create_companies_table.php.stub';

        if (is_file($path)) {
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

    /**
     * @return array<string, mixed>
     */
    protected function sampleCompanyAttributes(): array
    {
        return [
            'status' => 'draft',
            'name' => 'Muster GmbH',
            'display_name' => 'Muster GmbH',
            'company_type' => 'customer',
            'default_currency_code' => 'EUR',
            'is_active' => true,
        ];
    }
}
