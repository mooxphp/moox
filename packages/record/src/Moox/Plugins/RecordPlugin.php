<?php

namespace Moox\Record\Moox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Core\Support\Resources\ChildResourceRegistrar;
use Moox\Record\Moox\Entities\Records\Record\RecordResource;

class RecordPlugin implements Plugin
{
    public function getId(): string
    {
        return 'item';
    }

    public function register(Panel $panel): void
    {
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            RecordResource::class,
            'record',
            config('record.resources.record', []),
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
