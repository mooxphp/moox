<?php

declare(strict_types=1);

namespace Moox\Restore;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Restore\Resources\RestoreDestinationResource;

class RestoreDestinationPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'restore-destination';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            RestoreDestinationResource::class,
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
