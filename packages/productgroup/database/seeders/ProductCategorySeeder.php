<?php

declare(strict_types=1);

namespace Moox\ProductCategory\Database\Seeders;

use Illuminate\Database\Seeder;
use Moox\ProductCategory\Models\ProductCategory;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        ProductCategory::factory()
            ->count(10)
            ->create();
    }
}
