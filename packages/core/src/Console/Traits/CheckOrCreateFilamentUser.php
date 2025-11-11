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

        alert("🚨 No users found. Let's create the first user");
        $this->createFilamentUser($userModel);
    }
 
    protected function createFilamentUser(string $userModel): void
    {
        info("🧑 Creating new admin user for model '{$userModel}'...");

        $name = text('Enter name', default: 'Admin');
        $email = text('Enter email', default: 'admin@example.com');
        $password = password('Enter password', required: true);

        $user = $userModel::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        info("✅ User '{$user->email}' created successfully.");
    }
   
    public function checkOrCreateWpUser(): void
    {
        $wpUserModel = \Moox\Press\Models\WpUser::class;

        if (! class_exists($wpUserModel)) {
            warning("⚠️ WP User model '{$wpUserModel}' does not exist.");
            return;
        }

        $table = (new $wpUserModel)->getTable();

        info("🔍 Checking WP user setup for Press Panel [Model: {$wpUserModel}]...");

        if (! Schema::hasTable($table)) {
            warning("⚠️ Table '{$table}' not found. Did you run migrations?");
            return;
        }

        if ($wpUserModel::count() > 0) {
            info("✅ Found existing WP users in '{$table}'. Skipping user creation.");
            return;
        }

        alert("🚨 No WP users found. Let's create the first WP user");
        $this->createWpUser($wpUserModel);
    }

    protected function createWpUser(string $wpUserModel): void
    {
        info("🧑 Creating new WP user for model '{$wpUserModel}'...");

        $login = text('Enter login', default: 'wpadmin');
        $email = text('Enter email', default: 'wpadmin@example.com');
        $password = password('Enter password', required: true);
        $displayName = text('Enter display name', default: $login);

        $user = $wpUserModel::create([
            'user_login' => $login,
            'user_email' => $email,
            'user_pass' => $password,
            'display_name' => $displayName,
            'user_registered' => now(),
        ]);

        info("✅ WP user '{$user->user_login}' created successfully.");
    }

}
