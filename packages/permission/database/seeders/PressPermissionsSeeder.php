<?php

namespace Moox\Permission\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PressPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'view',
            'edit',
            'create',
            'delete',
            'restore',
            'publish',
            'view own',
            'edit own',
            'delete own',
            'publish own',
            'bulk modify',
            'time travel',
            'force delete',
        ];

        $guardName = 'press';

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guardName]);
        }
    }
}
