<?php

namespace Moox\RedisModel;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\RedisModel\Resources\RedisModelResource;

class RedisModelPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'redis-model';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            RedisModelResource::class,
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
