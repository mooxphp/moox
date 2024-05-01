<?php

namespace Moox\Passkey;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Passkey\Resources\PasskeyResource;

class PasskeyPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'passkey';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PasskeyResource::class,
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
