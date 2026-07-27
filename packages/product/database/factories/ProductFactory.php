<?php

declare(strict_types=1);

namespace Moox\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
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
            $locale = (string) config('app.locale', 'en');

            $product->translateOrNew($locale)
                ->fill($this->defaultTranslatedAttributes())
                ->save();
        });
    }

    public function definition(): array
    {
        return [
            'sku' => 'SKU-'.strtoupper($this->faker->unique()->bothify('??####')),
            'type' => $this->faker->randomElement(array_keys(config('product.types', ['simple' => 'simple']))),
            'status' => $this->faker->randomElement(array_keys(config('product.statuses', []))),
            'price' => $this->faker->randomFloat(2, 5, 500),
            'sale_price' => $this->faker->optional(0.3)->randomFloat(2, 3, 400),
            'cost_price' => $this->faker->optional(0.5)->randomFloat(2, 2, 300),
            'stock' => $this->faker->numberBetween(0, 250),
            'stock_min' => $this->faker->numberBetween(0, 10),
            'weight' => $this->faker->optional(0.8)->randomFloat(3, 0.05, 25),
            'weight_unit' => $this->faker->optional(0.8)->randomElement(['kg', 'g']),
            'unit_of_measure' => $this->faker->optional(0.7)->randomElement(['piece', 'm', 'kg']),
            'is_purchasable' => true,
            'is_sellable' => true,
            'custom_properties' => [
                'source' => 'factory',
            ],
        ];
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
        $name = $this->faker->words(3, true);
        $slug = Str::slug($name).'-'.Str::lower(Str::random(4));

        return [
            'name' => $name,
            'slug' => $slug,
            'short_description' => $this->faker->optional(0.8)->sentence(12) ?? '',
            'description' => $this->faker->paragraph(),
            'meta_title' => $this->faker->optional(0.6)->sentence(6) ?? '',
            'meta_description' => $this->faker->optional(0.6)->text(140) ?? '',
        ];
    }
}
