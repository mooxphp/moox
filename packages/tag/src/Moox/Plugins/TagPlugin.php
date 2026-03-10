<?php

declare(strict_types=1);

namespace Moox\Tag\Moox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Tag\Resources\TagResource;

class TagPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'tag';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            TagResource::class,
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
