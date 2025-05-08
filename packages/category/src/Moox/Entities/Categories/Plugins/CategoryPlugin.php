<?php

declare(strict_types=1);

namespace Moox\Category\Moox\Entities\Categories\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Category\Moox\Entities\Categories\Category\CategoryResource;

class CategoryPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'category';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            CategoryResource::class,
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
