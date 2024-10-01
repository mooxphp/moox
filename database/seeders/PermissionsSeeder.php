<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Permission::create([
            'id' => 1,
            'name' => 'view_blog',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 2,
            'name' => 'view_any_blog',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 3,
            'name' => 'create_blog',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 4,
            'name' => 'update_blog',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 5,
            'name' => 'restore_blog',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 6,
            'name' => 'restore_any_blog',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 7,
            'name' => 'replicate_blog',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 8,
            'name' => 'reorder_blog',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 9,
            'name' => 'delete_blog',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 10,
            'name' => 'delete_any_blog',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 11,
            'name' => 'force_delete_blog',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 12,
            'name' => 'force_delete_any_blog',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 13,
            'name' => 'view_builder',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 14,
            'name' => 'view_any_builder',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 15,
            'name' => 'create_builder',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 16,
            'name' => 'update_builder',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 17,
            'name' => 'restore_builder',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 18,
            'name' => 'restore_any_builder',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 19,
            'name' => 'replicate_builder',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 20,
            'name' => 'reorder_builder',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 21,
            'name' => 'delete_builder',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 22,
            'name' => 'delete_any_builder',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 23,
            'name' => 'force_delete_builder',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 24,
            'name' => 'force_delete_any_builder',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 25,
            'name' => 'view_core',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 26,
            'name' => 'view_any_core',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 27,
            'name' => 'create_core',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 28,
            'name' => 'update_core',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 29,
            'name' => 'restore_core',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 30,
            'name' => 'restore_any_core',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 31,
            'name' => 'replicate_core',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 32,
            'name' => 'reorder_core',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 33,
            'name' => 'delete_core',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 34,
            'name' => 'delete_any_core',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 35,
            'name' => 'force_delete_core',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 36,
            'name' => 'force_delete_any_core',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 37,
            'name' => 'view_data',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 38,
            'name' => 'view_any_data',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 39,
            'name' => 'create_data',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 40,
            'name' => 'update_data',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 41,
            'name' => 'restore_data',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 42,
            'name' => 'restore_any_data',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 43,
            'name' => 'replicate_data',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 44,
            'name' => 'reorder_data',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 45,
            'name' => 'delete_data',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 46,
            'name' => 'delete_any_data',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 47,
            'name' => 'force_delete_data',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 48,
            'name' => 'force_delete_any_data',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 49,
            'name' => 'view_file',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 50,
            'name' => 'view_any_file',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 51,
            'name' => 'create_file',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 52,
            'name' => 'update_file',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 53,
            'name' => 'restore_file',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 54,
            'name' => 'restore_any_file',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 55,
            'name' => 'replicate_file',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 56,
            'name' => 'reorder_file',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 57,
            'name' => 'delete_file',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 58,
            'name' => 'delete_any_file',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 59,
            'name' => 'force_delete_file',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 60,
            'name' => 'force_delete_any_file',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 61,
            'name' => 'view_job::batches',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 62,
            'name' => 'view_any_job::batches',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 63,
            'name' => 'create_job::batches',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 64,
            'name' => 'update_job::batches',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 65,
            'name' => 'restore_job::batches',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 66,
            'name' => 'restore_any_job::batches',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 67,
            'name' => 'replicate_job::batches',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 68,
            'name' => 'reorder_job::batches',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 69,
            'name' => 'delete_job::batches',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 70,
            'name' => 'delete_any_job::batches',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 71,
            'name' => 'force_delete_job::batches',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 72,
            'name' => 'force_delete_any_job::batches',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 73,
            'name' => 'view_jobs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 74,
            'name' => 'view_any_jobs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 75,
            'name' => 'create_jobs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 76,
            'name' => 'update_jobs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 77,
            'name' => 'restore_jobs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 78,
            'name' => 'restore_any_jobs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 79,
            'name' => 'replicate_jobs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 80,
            'name' => 'reorder_jobs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 81,
            'name' => 'delete_jobs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 82,
            'name' => 'delete_any_jobs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 83,
            'name' => 'force_delete_jobs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 84,
            'name' => 'force_delete_any_jobs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 85,
            'name' => 'view_jobs::failed',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 86,
            'name' => 'view_any_jobs::failed',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 87,
            'name' => 'create_jobs::failed',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 88,
            'name' => 'update_jobs::failed',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 89,
            'name' => 'restore_jobs::failed',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 90,
            'name' => 'restore_any_jobs::failed',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 91,
            'name' => 'replicate_jobs::failed',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 92,
            'name' => 'reorder_jobs::failed',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 93,
            'name' => 'delete_jobs::failed',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 94,
            'name' => 'delete_any_jobs::failed',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 95,
            'name' => 'force_delete_jobs::failed',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 96,
            'name' => 'force_delete_any_jobs::failed',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 97,
            'name' => 'view_jobs::waiting',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 98,
            'name' => 'view_any_jobs::waiting',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 99,
            'name' => 'create_jobs::waiting',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 100,
            'name' => 'update_jobs::waiting',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 101,
            'name' => 'restore_jobs::waiting',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 102,
            'name' => 'restore_any_jobs::waiting',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 103,
            'name' => 'replicate_jobs::waiting',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 104,
            'name' => 'reorder_jobs::waiting',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 105,
            'name' => 'delete_jobs::waiting',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 106,
            'name' => 'delete_any_jobs::waiting',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 107,
            'name' => 'force_delete_jobs::waiting',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 108,
            'name' => 'force_delete_any_jobs::waiting',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 109,
            'name' => 'view_logs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 110,
            'name' => 'view_any_logs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 111,
            'name' => 'create_logs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 112,
            'name' => 'update_logs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 113,
            'name' => 'restore_logs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 114,
            'name' => 'restore_any_logs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 115,
            'name' => 'replicate_logs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 116,
            'name' => 'reorder_logs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 117,
            'name' => 'delete_logs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 118,
            'name' => 'delete_any_logs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 119,
            'name' => 'force_delete_logs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 120,
            'name' => 'force_delete_any_logs',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 121,
            'name' => 'view_page',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 122,
            'name' => 'view_any_page',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 123,
            'name' => 'create_page',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 124,
            'name' => 'update_page',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 125,
            'name' => 'restore_page',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 126,
            'name' => 'restore_any_page',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 127,
            'name' => 'replicate_page',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 128,
            'name' => 'reorder_page',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 129,
            'name' => 'delete_page',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 130,
            'name' => 'delete_any_page',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 131,
            'name' => 'force_delete_page',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 132,
            'name' => 'force_delete_any_page',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 133,
            'name' => 'view_role',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 134,
            'name' => 'view_any_role',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 135,
            'name' => 'create_role',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 136,
            'name' => 'update_role',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 137,
            'name' => 'delete_role',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 138,
            'name' => 'delete_any_role',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 139,
            'name' => 'view_user',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 140,
            'name' => 'view_any_user',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 141,
            'name' => 'create_user',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 142,
            'name' => 'update_user',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 143,
            'name' => 'restore_user',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 144,
            'name' => 'restore_any_user',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 145,
            'name' => 'replicate_user',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 146,
            'name' => 'reorder_user',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 147,
            'name' => 'delete_user',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 148,
            'name' => 'delete_any_user',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 149,
            'name' => 'force_delete_user',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 150,
            'name' => 'force_delete_any_user',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);

        Permission::create([
            'id' => 151,
            'name' => 'page_MyProfilePage',
            'guard_name' => 'web',
            'created_at' => '2023-12-10 23:32:42',
            'updated_at' => '2023-12-10 23:32:42',
        ]);
    }
}
