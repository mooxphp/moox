<?php

declare(strict_types=1);

namespace Moox\PressWiki\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\PressWiki\Resources\WpWikiLocationTopicResource;

class WpWikiLocationTopicPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'wp-wiki-location-topic';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            WpWikiLocationTopicResource::class,
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
