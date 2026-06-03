<?php

declare(strict_types=1);

namespace Moox\Press\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Press\Resources\WpTermTaxonomyResource;

class WpTermTaxonomyPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'wp-term_taxonomy';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            WpTermTaxonomyResource::class,
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
