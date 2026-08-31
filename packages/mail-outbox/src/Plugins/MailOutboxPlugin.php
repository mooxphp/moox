<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\MailOutbox\Resources\MailSendLogResource;

final class MailOutboxPlugin implements Plugin
{
    public function getId(): string
    {
        return 'mail-outbox';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MailSendLogResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
    }

    public static function make(): static
    {
        return app(self::class);
    }
}
