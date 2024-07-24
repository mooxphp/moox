<?php

namespace Moox\Expiry;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Expiry\Resources\ExpiryResource;

class ExpiryPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'expiry';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            ExpiryResource::class,
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
