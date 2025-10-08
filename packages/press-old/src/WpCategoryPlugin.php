<?php

namespace Moox\Press;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Press\Resources\WpCategoryResource;

class WpCategoryPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'wp-category';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            WpCategoryResource::class,
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
