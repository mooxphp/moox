<?php

namespace Moox\Item\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\Item\ItemServiceProvider;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;

#[WithMigration('laravel', 'cache', 'queue')]
#[WithMigration('session')]
class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
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
        $userClass = config('item.auth.user', 'Testbench\\Models\\User');

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
