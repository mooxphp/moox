<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Role::create([
            'id' => 1,
            'name' => 'super_admin',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Role::create([
            'id' => 2,
            'name' => 'filament_user',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:43',
            'updated_at' => '2023-12-10 23:32:43',
        ]);
    }
}
