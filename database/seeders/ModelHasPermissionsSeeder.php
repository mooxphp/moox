<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelHasPermissionsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $path = 'database/seeders/model_has_permissions.sql';
        DB::unprepared(file_get_contents($path));
        $this->command->info('ModelHasPermissions table seeded!');
    }
}
