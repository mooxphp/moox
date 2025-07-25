<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\text;
use function Laravel\Prompts\password;

trait CheckOrCreateFilamentUser
{
    public function checkOrCreateFilamentUser(): void
    {
        $userModel = config('filament.auth.providers.users.model') ?? \App\Models\User::class;

        if (! Schema::hasTable((new $userModel)->getTable())) {
            warning('User table not found. Did you run migrations?');
            return;
        }

        if ($userModel::count() > 0) {
            info('There are already users. Skipping user creation.');
            return;
        }

        $this->createFilamentUser($userModel);
    }

    protected function createFilamentUser($userModel): void
    {
        $name = text('Enter a name for the admin user');
        $email = text('Enter an email for the admin user');
        $password = password('Enter a password for the admin user');

        $userModel::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        info("User {$email} created successfully.");
    }
}
