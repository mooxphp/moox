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
                'title' => 'Login',
                'context' => LinkProcessContext::AUTH,
                'mail_from' => null,
                'content' => null,
                'template_key' => 'login',
                'handler_key' => RedemptionHandlerRegistry::DEFAULT_PROCESS,
                'expiry_minutes' => null,
                'invalidate_prior' => true,
            ],
        );

        LoginLinkProcess::query()->updateOrCreate(
            ['slug' => 'ack'],
            [
                'title' => 'Acknowledge',
                'context' => LinkProcessContext::PUBLIC,
                'mail_from' => null,
                'content' => null,
                'template_key' => 'ack',
                'handler_key' => 'ack',
                'expiry_minutes' => null,
                'invalidate_prior' => true,
            ],
        );

        LoginLinkProcess::query()->updateOrCreate(
            ['slug' => 'demo-dump'],
            [
                'title' => 'Demo dump (verify-style)',
                'context' => LinkProcessContext::PUBLIC,
                'mail_from' => null,
                'content' => null,
                'template_key' => 'dump',
                'handler_key' => 'dump',
                'expiry_minutes' => 60,
                'invalidate_prior' => true,
            ],
        );

        LoginLinkProcess::query()->updateOrCreate(
            ['slug' => 'demo-campaign'],
            [
                'title' => 'Demo dump (campaign / mass)',
                'context' => LinkProcessContext::PUBLIC,
                'mail_from' => null,
                'content' => null,
                'template_key' => 'dump',
                'handler_key' => 'dump',
                'expiry_minutes' => 60,
                'invalidate_prior' => false,
            ],
        );
    }
}
