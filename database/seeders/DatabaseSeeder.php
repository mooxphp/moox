<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $this->call(ModelHasPermissionsSeeder::class);
        $this->call(ModelHasRolesSeeder::class);
        $this->call(PermissionsSeeder::class);
        $this->call(RolesHasPermissionsSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(UsersSeeder::class);

    }
}
