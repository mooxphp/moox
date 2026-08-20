<?php

declare(strict_types=1);

namespace Moox\MailInbox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\MailInbox\Resources\InboxMessageResource;
use Moox\MailInbox\Resources\MailInboxSyncStateResource;

final class MailInboxPlugin implements Plugin
{
    public function getId(): string
    {
        return 'mail-inbox';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            InboxMessageResource::class,
            MailInboxSyncStateResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return app(self::class);
    }
}
