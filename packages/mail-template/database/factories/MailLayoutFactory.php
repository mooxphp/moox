<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Moox\Localization\Models\Localization;
use Moox\MailTemplate\Models\MailLayout;

/**
 * @extends Factory<MailLayout>
 */
class MailLayoutFactory extends Factory
{
    protected $model = MailLayout::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->bothify('layout-##??'),
            'status' => 'draft',
            'background_color' => '#ECF2F6',
            'button_color' => '#005CA3',
            'text_color' => '#000000',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Model $layout): void {
            if (! $layout instanceof MailLayout || $layout->translations()->exists()) {
                return;
            }

            $layout->translateOrNew($this->defaultLocale())->fill([
                'title' => 'Demo layout',
                'footer' => null,
            ])->save();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function translation(array $attributes, ?string $locale = null): static
    {
        return $this->afterCreating(function (Model $layout) use ($attributes, $locale): void {
            if (! $layout instanceof MailLayout) {
                return;
            }

            $layout->translateOrNew($locale ?? $this->defaultLocale())->fill($attributes)->save();
        });
    }

    private function defaultLocale(): string
    {
        $variant = Localization::query()->where('is_default', true)->value('locale_variant');

        if (is_string($variant) && $variant !== '') {
            return $variant;
        }

        return 'de_DE';
    }
}
