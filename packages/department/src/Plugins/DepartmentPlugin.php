<?php

declare(strict_types=1);

namespace Moox\Department\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Department\Resources\DepartmentPersonResource;
use Moox\Department\Resources\DepartmentResource;

class DepartmentPlugin implements Plugin
{
    public function getId(): string
    {
        return 'department';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            DepartmentResource::class,
            DepartmentPersonResource::class,
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
