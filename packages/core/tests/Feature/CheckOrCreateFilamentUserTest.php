<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Use in-memory sqlite for isolation
    config(['database.default' => 'sqlite']);
    config(['database.connections.sqlite.database' => ':memory:']);

    // Set up a test user model
    eval('namespace App\\Models; use Illuminate\\Database\\Eloquent\\Model; class User extends Model { protected $guarded = []; }');
    config(['filament.auth.providers.users.model' => App\Models\User::class]);
});

test('warns if user table does not exist', function () {
    $this->artisan('test:filament-user')
        ->expectsOutput('User table not found. Did you run migrations?')
        ->assertExitCode(0);
});

test('skips creation if users exist', function () {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });
    \App\Models\User::create([
        'name' => 'Existing',
        'email' => 'existing@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->artisan('test:filament-user')
        ->expectsOutput('There are already users. Skipping user creation.')
        ->assertExitCode(0);
});

test('creates a user if none exist', function () {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });

    $this->artisan('test:filament-user')
        ->expectsQuestion('Enter a name for the admin user', 'Test User')
        ->expectsQuestion('Enter an email for the admin user', 'test@example.com')
        ->expectsQuestion('Enter a password for the admin user', 'secret')
        ->expectsOutput('User test@example.com created successfully.')
        ->assertExitCode(0);

    $user = \App\Models\User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect(Hash::check('secret', $user->password))->toBeTrue();
});

// Register a test command using the trait
if (! Artisan::has('test:filament-user')) {
    \Illuminate\Console\Command::macro('handle', function () {
        return $this->checkOrCreateFilamentUser();
    });
    Artisan::command('test:filament-user', function () {
        return $this->checkOrCreateFilamentUser();
    })->describe('Test command for CheckOrCreateFilamentUser')->uses(\Moox\Core\Console\Traits\CheckOrCreateFilamentUser::class);
}
