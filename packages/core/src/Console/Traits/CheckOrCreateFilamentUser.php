<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

trait CheckOrCreateFilamentUser
{
    public function checkOrCreateFilamentUser(): void
    {
        $userModel = config('filament.auth.providers.users.model') ?? \App\Models\User::class;

        if (! Schema::hasTable((new $userModel)->getTable())) {
            $this->warn('User table not found. Did you run migrations?');

            return;
        }

        if ($userModel::count() > 0) {
            $this->info('There are already users. Skipping user creation.');

            return;
        }

        $this->createFilamentUser($userModel);
    }

    protected function createFilamentUser($userModel): void
    {
        $name = $this->ask('Enter a name for the admin user');
        $email = $this->ask('Enter an email for the admin user');
        $password = $this->secret('Enter a password for the admin user');

        $userModel::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("User {$email} created successfully.");
    }
}
