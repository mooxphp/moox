<?php

declare(strict_types=1);

namespace Moox\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Moox\Product\Enums\ProductType;
use Moox\Product\Models\Product;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            $locale = app()->getLocale();

            $product->translateOrNew($locale)
                ->fill($this->defaultTranslatedAttributes())
                ->save();
        });
    }

    public function definition(): array
    {
        return [
            'product_category_id' => 1,
            'type' => ProductType::Simple,
            'sku' => 'SKU-'.strtoupper($this->faker->unique()->bothify('??####')),
            'is_orderable' => false,
            'is_active' => true,
            'sort_order' => 0,
            'created_by_id' => 1,
            'created_by_type' => 'user',
            'updated_by_id' => 1,
            'updated_by_type' => 'user',
        ];
    }

    public function grouped(): static
    {
        return $this->state([
            'type' => ProductType::Grouped,
            'sku' => null,
            'is_orderable' => false,
            'is_canonical' => false,
            'canonical_product_id' => null,
        ]);
    }

    public function withoutTranslations(): static
    {
        return $this->withoutAfterCreating();
    }

    /**
     * @param  array<string, array<string, mixed>>  $attributesByLocale
     */
    public function withTranslationAttributes(array $attributesByLocale): static
    {
        return $this->withoutAfterCreating()->afterCreating(function (Product $product) use ($attributesByLocale): void {
            foreach ($attributesByLocale as $locale => $overrides) {
                $product->translateOrNew($locale)
                    ->fill(array_merge($this->defaultTranslatedAttributes(), $overrides))
                    ->save();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function defaultTranslatedAttributes(): array
    {
        $title = rtrim($this->faker->sentence(3), '.');
        $slug = Str::slug($title).'-'.Str::lower(Str::random(4));

        return [
            'title' => $title,
            'slug' => $slug,
        ];
    }
}
