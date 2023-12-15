<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesHasPermissionsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $path = 'role_has_permissions.sql';
        DB::unprepared(file_get_contents($path));
        $this->command->info('RolesHasPermissions table seeded!');
    }
}
