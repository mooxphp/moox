<?php

namespace Moox\Page;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Page\Resources\PageResource;

class PagePlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'page';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PageResource::class,
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
