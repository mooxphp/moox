<?php

declare(strict_types=1);

namespace Moox\Category\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Category\Resources\CategoryTreeResource;

class CategoryTreePlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'category-tree';
    }

    public function register(Panel $panel): void
    {
        // Tree is an alternate UI for categories only. Scoped children (media, tag, …)
        // are registered once by CategoryPlugin under source "category".
        $panel->resources([
            CategoryTreeResource::class,
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
