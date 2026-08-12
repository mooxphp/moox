<?php

declare(strict_types=1);

namespace Moox\LoginLink\Database\Seeders;

use Illuminate\Database\Seeder;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;

class LoginLinkProcessSeeder extends Seeder
{
    public function run(): void
    {
        LoginLinkProcess::query()->updateOrCreate(
            ['slug' => RedemptionHandlerRegistry::DEFAULT_PROCESS],
            [
                'title' => 'Login',
                'mail_from' => null,
                'content' => null,
                'handler_key' => RedemptionHandlerRegistry::DEFAULT_PROCESS,
                'expiry_minutes' => null,
            ],
        );

        LoginLinkProcess::query()->updateOrCreate(
            ['slug' => 'ack'],
            [
                'title' => 'Acknowledge',
                'mail_from' => null,
                'content' => 'Click the button below to confirm.',
                'handler_key' => 'ack',
                'expiry_minutes' => null,
            ],
        );
    }
}
