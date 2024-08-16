<?php

namespace Moox\Permission;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Permission\Resources\PermissionResource;

class PermissionPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'permission';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PermissionResource::class,
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
