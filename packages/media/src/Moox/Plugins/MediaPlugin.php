<?php

namespace Moox\Media\Moox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Core\Support\Resources\ChildResourceRegistrar;
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
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            MediaResource::class,
            'media',
            config('media.resources.media', []),
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
