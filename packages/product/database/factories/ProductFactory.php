<?php

declare(strict_types=1);

namespace Moox\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Moox\Attribute\Models\Attribute;
use Moox\Attribute\Models\AttributeValues;
use Moox\Product\Models\Product;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product, ?Model $parent = null): void {
            $this->seedDefaultTranslation($product);
            $this->seedDefaultAttributeValues($product);
        });
    }

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'ulid' => (string) Str::ulid(),
            'is_active' => true,
            'image' => null,
            'type' => null,
            'due_at' => null,
            'color' => null,
            'sku' => 'SKU-'.strtoupper($this->faker->unique()->bothify('??####')),
            'gtin' => null,
            'mpn' => null,
            'brand_name' => $this->faker->company(),
            'weight_grams' => $this->faker->numberBetween(50, 5000),
            'length_mm' => $this->faker->numberBetween(50, 800),
            'width_mm' => $this->faker->numberBetween(30, 500),
            'height_mm' => $this->faker->numberBetween(10, 300),
            'status' => 'draft',
            'custom_properties' => null,
        ];
    }

    /**
     * Persist the product without any translation rows (attribute values are still attached).
     */
    public function withoutTranslations(): static
    {
        return $this->withoutAfterCreating()->afterCreating(function (Product $product, ?Model $parent = null): void {
            $this->seedDefaultAttributeValues($product);
        });
    }

    /**
     * @param  string  ...$locales
     */
    public function withTranslationLocales(string ...$locales): static
    {
        $locales = array_values(array_unique($locales));

        return $this->withoutAfterCreating()->afterCreating(function (Product $product, ?Model $parent = null) use ($locales): void {
            foreach ($locales as $locale) {
                $product->translateOrNew($locale)
                    ->fill($this->defaultTranslatedAttributes())
                    ->save();
            }
            $this->seedDefaultAttributeValues($product);
        });
    }

    /**
     * @param  array<string, array<string, mixed>>  $attributesByLocale
     */
    public function withTranslationAttributes(array $attributesByLocale): static
    {
        return $this->withoutAfterCreating()->afterCreating(function (Product $product, ?Model $parent = null) use ($attributesByLocale): void {
            foreach ($attributesByLocale as $locale => $overrides) {
                $product->translateOrNew($locale)
                    ->fill(array_merge($this->defaultTranslatedAttributes(), $overrides))
                    ->save();
            }
            $this->seedDefaultAttributeValues($product);
        });
    }

    public function withoutAttributeValues(): static
    {
        return $this->withoutAfterCreating()->afterCreating(function (Product $product, ?Model $parent = null): void {
            $this->seedDefaultTranslation($product);
        });
    }

    protected function seedDefaultTranslation(Product $product): void
    {
        $locale = (string) config('app.locale', 'en');

        $product->translateOrNew($locale)
            ->fill($this->defaultTranslatedAttributes())
            ->save();
    }

    protected function seedDefaultAttributeValues(Product $product): void
    {
        $attribute = Attribute::query()->create([
            'type' => 'text',
            'name' => 'Factory '.$this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'status' => 'draft',
            'uuid' => (string) Str::uuid(),
            'ulid' => (string) Str::ulid(),
        ]);

        $value = AttributeValues::query()->create([
            'attribute_id' => $attribute->getKey(),
            'value' => [
                'key' => 'factory-'.$this->faker->lexify('????'),
                'label' => $this->faker->word(),
            ],
        ]);

        $product->attributeValues()->attach($value->getKey());
    }

    /**
     * @return array<string, string>
     */
    protected function defaultTranslatedAttributes(): array
    {
        $slug = 'product-'.strtolower($this->faker->unique()->lexify('????????'));
        $title = $this->faker->sentence(3);

        return [
            'title' => $title,
            'slug' => $slug,
            'permalink' => 'https://example.test/products/'.$slug,
            'subtitle' => $this->faker->optional(0.7)->sentence(4) ?: '',
            'excerpt' => $this->faker->optional(0.8)->text(180) ?: '',
            'description' => $this->faker->paragraph(),
            'content' => $this->faker->paragraphs(2, true),
            'meta_title' => $this->faker->optional(0.6)->sentence(6) ?: '',
            'meta_description' => $this->faker->optional(0.6)->text(140) ?: '',
        ];
    }
}
