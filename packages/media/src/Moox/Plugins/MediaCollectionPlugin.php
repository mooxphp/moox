<?php

namespace Moox\Media\Moox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Media\Resources\MediaCollectionResource;

class MediaCollectionPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'media-collection';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MediaCollectionResource::class,
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
