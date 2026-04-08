<?php

declare(strict_types=1);

namespace Moox\Category\Moox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Category\Moox\Entities\Categories\Category\CategoryResource;
use Moox\Core\Support\Resources\ChildResourceRegistrar;
use Moox\Core\Support\Resources\ResourceNavigationRegistrar;

class CategoryPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'category';
    }

    public function register(Panel $panel): void
    {
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            CategoryResource::class,
            'category',
            config('category.resources.category', []),
        );

        ResourceNavigationRegistrar::register($panel, [
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
