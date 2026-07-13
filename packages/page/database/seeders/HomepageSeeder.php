<?php

declare(strict_types=1);

namespace Moox\Page\Database\Seeders;

use Illuminate\Database\Seeder;

class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PageSeeder::class);
    }
}
