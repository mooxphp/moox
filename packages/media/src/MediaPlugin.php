<?php

namespace Moox\Media;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Moox\Media\Resources\MediaResource;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Media\Resources\MediaCollectionResource;

class MediaPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'media';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MediaResource::class,
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
