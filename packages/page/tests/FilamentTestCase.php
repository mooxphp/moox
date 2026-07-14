<?php

declare(strict_types=1);

namespace Moox\Page\Tests;

use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Moox\DevTools\Models\TestUser;
use Orchestra\Testbench\TestCase as Orchestra;

if (class_exists(Orchestra::class)) {
    abstract class FilamentTestCase extends TestCase
    {
        protected function setUp(): void
        {
            parent::setUp();

            $this->withoutVite();
            $this->setUpFilamentPanel();
            $this->actingAs($this->createTestUser());
        }

        protected function getEnvironmentSetUp($app): void
        {
            parent::getEnvironmentSetUp($app);

            $app['config']->set('auth.providers.users.model', TestUser::class);
            $app['config']->set('core.use_google_icons', true);

            Filament::registerPanel(Concerns\ProvidesFilament::panel());
        }

        protected function getPackageProviders($app): array
        {
            return array_merge(parent::getPackageProviders($app), Concerns\ProvidesFilament::providers());
        }

        protected function setUpFilamentPanel(): void
        {
            $panel = Filament::getPanel('admin');
            Filament::setCurrentPanel($panel);
            Filament::bootCurrentPanel();
        }

        protected function createTestUser(): TestUser
        {
            return TestUser::query()->create([
                'name' => 'Test User',
                'email' => 'test-'.uniqid().'@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        protected function livewire(string $component, array $params = []): \Livewire\Features\SupportTesting\Testable
        {
            return Livewire::test($component, $params);
        }
    }
} else {
    abstract class FilamentTestCase extends TestCase
    {
        protected function setUp(): void
        {
            parent::setUp();

            $this->actingAs($this->createTestUser());

            $panel = Filament::getPanel('admin');
            Filament::setCurrentPanel($panel);
            Filament::bootCurrentPanel();
        }

        protected function createTestUser(): User
        {
            return User::factory()->create();
        }

        protected function livewire(string $component, array $params = []): \Livewire\Features\SupportTesting\Testable
        {
            return Livewire::test($component, $params);
        }
    }
}
