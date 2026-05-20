<?php

declare(strict_types=1);

namespace Moox\Category\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Category\Resources\CategoryTreeResource;
use Moox\Core\Support\Resources\ChildResourceRegistrar;

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
            CategoryTreeResource::class,
            'category',
            config('category.resources.category', []),
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
