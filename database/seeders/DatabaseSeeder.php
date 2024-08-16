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

        // From the permission package, emtpy
        $this->call(ModelHasPermissionsSeeder::class);

        // From the permission package
        $this->call(ModelHasRolesSeeder::class);

        // From the permission package
        $this->call(PermissionsSeeder::class);

        // From the permission package, all
        $this->call(RolesHasPermissionsSeeder::class);

        // From the permission package, roles for moox and press
        $this->call(RolesSeeder::class);

        // From the permission package, demo data
        $this->call(UsersSeeder::class);

    }
}
