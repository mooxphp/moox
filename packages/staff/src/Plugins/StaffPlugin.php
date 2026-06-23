<?php

declare(strict_types=1);

namespace Moox\Staff\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Core\Support\Resources\ChildResourceRegistrar;
use Moox\Staff\Resources\StaffResource;

class StaffPlugin implements Plugin
{
    public function getId(): string
    {
        return 'staff';
    }

    public function register(Panel $panel): void
    {
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            StaffResource::class,
            'staff',
            config('staff.resources.staff', []),
        );
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
