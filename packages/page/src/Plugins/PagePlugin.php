<?php

declare(strict_types=1);

namespace Moox\Page\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Core\Support\Resources\ChildResourceRegistrar;
use Moox\Page\Resources\PageResource;

class PagePlugin implements Plugin
{
    public function getId(): string
    {
        return 'page';
    }

    public function register(Panel $panel): void
    {
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            PageResource::class,
            'page',
            config('page.resources.page', []),
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
