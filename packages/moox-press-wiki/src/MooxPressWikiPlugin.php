<?php

namespace Moox\MooxPressWiki;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\MooxPressWiki\Resources\MooxPressWikiResource;

class MooxPressWikiPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'moox-press-wiki';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MooxPressWikiResource::class,
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
