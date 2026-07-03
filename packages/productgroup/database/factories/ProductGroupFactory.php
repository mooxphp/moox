<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Moox\ProductGroup\Models\ProductGroup;

/**
 * @extends Factory<ProductGroup>
 */
class ProductGroupFactory extends Factory
{
    protected $model = ProductGroup::class;

    public function configure(): static
    {
        return $this->afterCreating(function (ProductGroup $productGroup): void {
            $locale = (string) config('app.locale', 'en');

            $productGroup->translateOrNew($locale)
                ->fill($this->defaultTranslatedAttributes())
                ->save();
        });
    }

    public function definition(): array
    {
        return [
            'code' => 'PG-'.strtoupper($this->faker->unique()->bothify('??####')),
            'type' => $this->faker->randomElement(array_keys(config('productgroup.types', ['family' => 'family']))),
            'status' => $this->faker->randomElement(array_keys(config('productgroup.statuses', []))),
            'parent_id' => null,
            'attribute_set_id' => null,
            'default_unit' => $this->faker->optional(0.7)->randomElement(['Stück', 'm', 'kg']),
            'sku_prefix' => $this->faker->optional(0.5)->bothify('??.??'),
            'brand_id' => $this->faker->optional(0.3)->numberBetween(1, 50),
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
        return $this->withoutAfterCreating()->afterCreating(function (ProductGroup $productGroup) use ($attributesByLocale): void {
            foreach ($attributesByLocale as $locale => $overrides) {
                $productGroup->translateOrNew($locale)
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
