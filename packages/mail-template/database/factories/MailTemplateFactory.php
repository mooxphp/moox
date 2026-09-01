<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
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
            'key' => 'demo',
            'locale' => 'de',
            'view' => 'welcome',
            'brand_name' => 'Acme',
            'subject' => 'Demo',
            'logo_path' => null,
            'mail_content' => '<mj-text>Demo content</mj-text>',
            'footer' => '<mj-text font-size="12px" color="#777777">© Acme</mj-text>',
        ];
    }

    public function invoice(): static
    {
        return $this->state(fn (): array => [
            'key' => 'invoice',
            'subject' => 'Ihre Rechnung',
            'mail_content' => null,
            'footer' => null,
        ]);
    }
}
