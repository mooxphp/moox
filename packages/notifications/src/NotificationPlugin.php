<?php

namespace Moox\Notification;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Notification\Resources\NotificationResource;

class NotificationPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'notifications';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            NotificationResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
