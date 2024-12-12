<?php

declare(strict_types=1);

namespace Moox\Builder;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Builder\Resources\SimpleTaxonomyResource;

class SimpleTaxonomyPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'simple-taxonomy';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            SimpleTaxonomyResource::class,
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
