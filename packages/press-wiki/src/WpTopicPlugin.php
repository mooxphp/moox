<?php

namespace Moox\PressWiki;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\PressWiki\Resources\WpTopicResource;

class WpTopicPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'wp-thema';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            WpTopicResource::class,
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
