<?php

declare(strict_types=1);

namespace Moox\Press\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Press\Resources\WpCommentMetaResource;

class WpCommentMetaPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'wp-commentmeta';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            WpCommentMetaResource::class,
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
