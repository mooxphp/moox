<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Database\Seeders;

use Illuminate\Database\Seeder;
use Moox\ProductGroup\Models\ProductGroup;

class ProductGroupSeeder extends Seeder
{
    public function run(): void
    {
        ProductGroup::factory()
            ->count(10)
            ->create();
    }
}
