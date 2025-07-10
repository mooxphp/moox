<?php

namespace Moox\Media;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Media\Resources\MediaResource;

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
