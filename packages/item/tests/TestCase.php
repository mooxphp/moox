<?php

namespace Moox\Item\Tests;

use Filament\Panel;
use Filament\Pages\Dashboard;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Moox\Core\CoreServiceProvider;
use Moox\Item\ItemServiceProvider;
use Filament\Widgets\AccountWidget;
use Illuminate\Support\ViewErrorBag;
use Filament\FilamentServiceProvider;
use Livewire\LivewireServiceProvider;
use Moox\Item\Moox\Plugins\ItemPlugin;
use Filament\Widgets\FilamentInfoWidget;
use Pest\Livewire\InteractsWithLivewire;
use Filament\Http\Middleware\Authenticate;
use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Session\Middleware\StartSession;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Orchestra\Testbench\Attributes\WithMigration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Support\MessageBag;

#[WithMigration('laravel', 'cache', 'queue')]
#[WithMigration('session')]
class TestCase extends Orchestra
{
    use RefreshDatabase, WithWorkbench, InteractsWithLivewire;

    protected function setUp(): void
    {
        parent::setUp();
    }
  
    
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Use in-memory session driver during tests to ensure errors bag works without DB.
        $app['config']->set('session.driver', 'array');

        // Ensure a non-null errors bag is always shared with views for Livewire.
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag->put('default', new MessageBag());
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
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,

                // wichtig: ShareErrorsFromSession MUSS NACH StartSession kommen
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
            ->spa()
            ->plugins([
                ItemPlugin::make(),
            ]);
            
        Filament::registerPanel($panel);
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            // Laravel Kernel essentials
           // Basis-Laravel Provider, die Testbench sonst nicht lädt
            \Illuminate\Auth\AuthServiceProvider::class,
            \Illuminate\Cookie\CookieServiceProvider::class,
            \Illuminate\Database\DatabaseServiceProvider::class,
            \Illuminate\Encryption\EncryptionServiceProvider::class,
            \Illuminate\Session\SessionServiceProvider::class,
            \Illuminate\Validation\ValidationServiceProvider::class,
            \Illuminate\View\ViewServiceProvider::class,
            \Illuminate\Pagination\PaginationServiceProvider::class,
            \Illuminate\Translation\TranslationServiceProvider::class,
            \Illuminate\Filesystem\FilesystemServiceProvider::class,
            
            \Filament\Support\SupportServiceProvider::class,

            // Filament Components  
            \Filament\Forms\FormsServiceProvider::class,
            \Filament\Tables\TablesServiceProvider::class,
            \Filament\Notifications\NotificationsServiceProvider::class,
            \Filament\Actions\ActionsServiceProvider::class,
            \Filament\Infolists\InfolistsServiceProvider::class,
            \Filament\Widgets\WidgetsServiceProvider::class,
            
            // Moox packages
            CoreServiceProvider::class,
            ItemServiceProvider::class,
        ];
    }


    protected function setUpTestUser(): array
    {
        // Create users table (included in Laravel migrations via WithMigration attribute)
        // Orchestra Testbench automatically creates users table with #[WithMigration('laravel')]

        return [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ];
    }

    protected function createTestUser(): object
    {
        $userData = $this->setUpTestUser();

        // Use Laravel's built-in User model for testing
        $userClass = config('item.auth.user', 'Moox\\DevTools\\Models\\TestUser');

        if (! class_exists($userClass)) {
            // Fallback to a simple test user
            $userClass = new class extends \Illuminate\Foundation\Auth\User
            {
                protected $table = 'users';

                protected $fillable = ['name', 'email', 'password'];

                protected $hidden = ['password'];
            };
        }

        return $userClass::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => bcrypt($userData['password']),
        ]);
    }

   
}
