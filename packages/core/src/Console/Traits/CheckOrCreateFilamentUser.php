<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\text;
use function Laravel\Prompts\password;

trait CheckOrCreateFilamentUser
{
    public function checkOrCreateFilamentUser(): void
    {
        /** @var class-string<Model> $userModel */
        $userModel = Config::get('filament.auth.providers.users.model') ?? \App\Models\User::class;

        if (! class_exists($userModel)) {
            warning("⚠️ User model '{$userModel}' does not exist.");
            return;
        }

        $table = (new $userModel)->getTable();

        info("🔍 Checking user setup for Filament panel [Model: {$userModel}]...");

        if (! Schema::hasTable($table)) {
            warning("⚠️ Table '{$table}' not found. Did you run migrations?");
            return;
        }

        if ($userModel::count() > 0) {
            info("✅ Found existing users in '{$table}'. Skipping user creation.");
            return;
        }

        alert("🚨 No users found in '{$table}'. Let's create the first Filament user.");
        $this->createFilamentUser($userModel);
    }

    protected function createFilamentUser(string $userModel): void
    {
        info("🧑 Creating new admin user for model '{$userModel}'...");

        $name = text('Enter name', default: 'Admin');
        $email = text('Enter email', default: 'admin@example.com');
        $password = password('Enter password (min. 6 characters)', required: true);

        if (strlen($password) < 6) {
            warning('⚠️ Password too short. Aborting.');
            return;
        }

        $user = $userModel::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        info("✅ User '{$user->email}' created successfully.");
    }
}
