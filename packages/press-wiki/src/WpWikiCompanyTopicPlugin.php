<?php

namespace Moox\PressWiki;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\PressWiki\Resources\WpWikiCompanyTopicResource;

class WpWikiCompanyTopicPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'wp-wiki-company-topic';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            WpWikiCompanyTopicResource::class,
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
