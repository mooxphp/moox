<?php

declare(strict_types=1);

namespace Moox\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Moox\Product\Models\Product;
use Moox\Product\Models\Variant;

/**
 * @extends Factory<Variant>
 */
class VariantFactory extends Factory
{
    protected $model = Variant::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Variant $variant): void {
            $locale = app()->getLocale();

            $variant->translateOrNew($locale)
                ->fill([
                    ...$this->defaultTranslatedAttributes(),
                    ...$this->auditAttributes(),
                ])
                ->save();
        });
    }

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => 'VAR-'.strtoupper($this->faker->unique()->bothify('??####')),
            'is_orderable' => false,
            'is_active' => true,
            'sort_order' => 0,
            ...$this->auditAttributes(),
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
        return $this->withoutAfterCreating()->afterCreating(function (Variant $variant) use ($attributesByLocale): void {
            foreach ($attributesByLocale as $locale => $overrides) {
                $variant->translateOrNew($locale)
                    ->fill(array_merge(
                        $this->defaultTranslatedAttributes(),
                        $this->auditAttributes(),
                        $overrides,
                    ))
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

    /**
     * @return array<string, int|string>
     */
    protected function auditAttributes(): array
    {
        return [
            'created_by_id' => 1,
            'created_by_type' => 'user',
            'updated_by_id' => 1,
            'updated_by_type' => 'user',
        ];
    }
}
