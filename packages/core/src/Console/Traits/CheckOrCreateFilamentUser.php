<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use function Laravel\Prompts\ask;
use function Laravel\Prompts\password;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

trait CheckOrCreateFilamentUser
{
    public function checkOrCreateFilamentUser(): void
    {
        $userModel = config('filament.auth.providers.users.model') ?? \App\Models\User::class;

        if (!Schema::hasTable((new $userModel)->getTable())) {
            warning('User table does not exist. Did you run migrations?');
            return;
        }

        if ($userModel::count() > 0) {
            info('At least one Filament user exists already.');
            return;
        }

        warning('No Filament user found. Creating a new user...');

        $name = ask('Enter admin user name');
        $email = ask('Enter admin user email');
        $userPassword = password('Enter admin user password');

        $userModel::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($userPassword),
        ]);

        info('Admin user created successfully!');
    }
}
