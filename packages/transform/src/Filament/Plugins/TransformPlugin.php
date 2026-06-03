<?php

declare(strict_types=1);

namespace Moox\Transform\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Transform\Filament\Resources\TransformDefinitionResource;
use Moox\Transform\Filament\Resources\TransformRecordResource;

class TransformPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'transform';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            TransformDefinitionResource::class,
            TransformRecordResource::class,
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
