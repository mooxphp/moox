<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelHasRolesSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $path = 'model_has_roles.sql';
        DB::unprepared(file_get_contents($path));
        $this->command->info('ModelHasRoles table seeded!');
    }
}
