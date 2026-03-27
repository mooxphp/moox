<?php

declare(strict_types=1);

namespace Moox\Tag\Moox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Core\Support\Resources\ResourceNavigationRegistrar;
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
        ResourceNavigationRegistrar::register($panel, [
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
