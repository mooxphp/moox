<?php

namespace Moox\Data\Database\Seeders;

use Illuminate\Database\Seeder;

class StaticTimezoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Moox\Data\Models\StaticTimezone::insert([
            [
                'name' => 'UTC',
                'offset_standard' => 'UTC',
                'dst' => false,
                'dst_start' => null,
                'dst_end' => null,
            ],
            [
                'name' => 'EST',
                'offset_standard' => '-0500',
                'dst' => true,
                'dst_start' => '2023-03-12 02:00:00',
                'dst_end' => '2023-11-05 02:00:00',
            ],
            [
                'name' => 'PST',
                'offset_standard' => '-0800',
                'dst' => true,
                'dst_start' => '2023-03-12 02:00:00',
                'dst_end' => '2023-11-05 02:00:00',
            ],
            [
                'name' => 'CET',
                'offset_standard' => '+0100',
                'dst' => true,
                'dst_start' => '2023-03-26 02:00:00',
                'dst_end' => '2023-10-29 02:00:00',
            ],
            [
                'name' => 'JST',
                'offset_standard' => '+0900',
                'dst' => false,
                'dst_start' => null,
                'dst_end' => null,
            ],
        ]);
    }
}
