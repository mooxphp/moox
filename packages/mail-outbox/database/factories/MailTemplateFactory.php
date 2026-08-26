<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\MailOutbox\Models\MailTemplate;

/**
 * @extends Factory<MailTemplate>
 */
class MailTemplateFactory extends Factory
{
    protected $model = MailTemplate::class;

    public function definition(): array
    {
        return [
            'key' => 'login-link',
            'locale' => 'de',
            'view' => 'login-link::mail.login-link',
            'brand_name' => 'Acme',
            'subject' => 'Login-Link',
            'logo_path' => null,
            'mail_content' => '<mj-text>bitte klicken Sie auf den folgenden Button, um sich anzumelden.</mj-text>',
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
