<?php

declare(strict_types=1);

namespace Moox\ProductCategory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Moox\ProductCategory\Models\ProductCategory;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function configure(): static
    {
        return $this->afterCreating(function (ProductCategory $productCategory): void {
            $locale = app()->getLocale();

            $productCategory->translateOrNew($locale)
                ->fill($this->defaultTranslatedAttributes())
                ->save();
        });
    }

    public function definition(): array
    {
        return [
            'is_active' => true,
            'parent_id' => null,
            'lft' => 0,
            'rgt' => 0,
            'sort_order' => 0,
            'created_by_id' => 1,
            'created_by_type' => 'user',
            'updated_by_id' => 1,
            'updated_by_type' => 'user',
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
        return $this->withoutAfterCreating()->afterCreating(function (ProductCategory $productCategory) use ($attributesByLocale): void {
            foreach ($attributesByLocale as $locale => $overrides) {
                $productCategory->translateOrNew($locale)
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
