<?php

declare(strict_types=1);

namespace Moox\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Moox\Product\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::factory()
            ->count(10)
            ->create();
    }
}
