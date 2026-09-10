<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Moox\Localization\Models\Localization;
use Moox\MailTemplate\Models\MailLayout;
use Moox\MailTemplate\Models\MailTemplate;

/**
 * @extends Factory<MailTemplate>
 */
class MailTemplateFactory extends Factory
{
    protected $model = MailTemplate::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->bothify('template-##??'),
            'mail_layout_id' => MailLayout::factory(),
            'logo' => null,
            'status' => 'draft',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Model $template): void {
            if (! $template instanceof MailTemplate || $template->translations()->exists()) {
                return;
            }

            $template->translateOrNew($this->defaultLocale())->fill([
                'title' => 'Demo',
                'mail_content' => '<mj-text>Demo content</mj-text>',
                'footer' => '<mj-text font-size="12px" color="#777777">© '.config('app.name').'</mj-text>',
            ])->save();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function translation(array $attributes, ?string $locale = null): static
    {
        return $this->afterCreating(function (Model $template) use ($attributes, $locale): void {
            if (! $template instanceof MailTemplate) {
                return;
            }

            $template->translateOrNew($locale ?? $this->defaultLocale())->fill($attributes)->save();
        });
    }

    public function invoice(): static
    {
        return $this->state(fn (): array => [
            'slug' => 'invoice',
        ])->translation([
            'title' => 'Ihre Rechnung',
            'mail_content' => '<mj-text>Rechnung Nr. {invoiceNumber}</mj-text>',
            'footer' => null,
        ]);
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
