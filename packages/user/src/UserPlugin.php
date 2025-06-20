<?php

namespace Moox\User;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\User\Resources\UserResource;

class UserPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'user';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            UserResource::class,
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
