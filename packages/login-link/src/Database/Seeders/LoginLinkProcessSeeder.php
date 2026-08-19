<?php

declare(strict_types=1);

namespace Moox\LoginLink\Database\Seeders;

use Illuminate\Database\Seeder;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Moox\LoginLink\Support\LinkProcessContext;

class LoginLinkProcessSeeder extends Seeder
{
    public function run(): void
    {
        LoginLinkProcess::query()->updateOrCreate(
            ['slug' => RedemptionHandlerRegistry::DEFAULT_PROCESS],
            [
                'title' => 'Passwordless login',
                'context' => LinkProcessContext::AUTH,
                'mail_from' => null,
                'content' => 'Click the button below to sign in. This link signs you into the panel.',
                'template_key' => 'login',
                'handler_key' => RedemptionHandlerRegistry::DEFAULT_PROCESS,
                'expiry_minutes' => null,
                'invalidate_prior' => true,
            ],
        );

        LoginLinkProcess::query()->updateOrCreate(
            ['slug' => 'verify-email'],
            [
                'title' => 'Email verification',
                'context' => LinkProcessContext::PUBLIC,
                'mail_from' => null,
                'content' => 'Confirm that you own this mailbox. This does not sign you in.',
                'template_key' => 'verify-email',
                'handler_key' => 'verify-email',
                'expiry_minutes' => 60,
                'invalidate_prior' => true,
            ],
        );

        LoginLinkProcess::query()->updateOrCreate(
            ['slug' => 'mass-mail'],
            [
                'title' => 'Mass mail verification',
                'context' => LinkProcessContext::PUBLIC,
                'mail_from' => null,
                'content' => 'Confirm that you received this mailing. Other recipients keep their own links.',
                'template_key' => 'mass-mail',
                'handler_key' => 'mass-mail',
                'expiry_minutes' => 60,
                'invalidate_prior' => false,
            ],
        );

        LoginLinkProcess::query()
            ->whereIn('slug', ['ack', 'demo-dump', 'demo-campaign'])
            ->delete();
    }
}
