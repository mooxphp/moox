<?php

namespace Moox\News\Moox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\News\Moox\Entities\News\News\NewsResource;

class NewsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'news';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                NewsResource::class,
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
