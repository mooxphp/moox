<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
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
            'layout' => 'welcome',
            'logo_path' => null,
            'status' => 'draft',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Model $template): void {
            if (! $template instanceof MailTemplate || $template->translations()->exists()) {
                return;
            }

            $locale = app()->getLocale() ?: 'de';

            $template->translateOrNew($locale)->fill([
                'brand_name' => 'Acme',
                'title' => 'Demo',
                'mail_content' => '<mj-text>Demo content</mj-text>',
                'footer' => '<mj-text font-size="12px" color="#777777">© Acme</mj-text>',
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

            $locale ??= app()->getLocale() ?: 'de';
            $template->translateOrNew($locale)->fill($attributes)->save();
        });
    }

    public function invoice(): static
    {
        return $this->state(fn (): array => [
            'slug' => 'invoice',
        ])->translation([
            'title' => 'Ihre Rechnung',
            'mail_content' => null,
            'footer' => null,
        ]);
    }
}
