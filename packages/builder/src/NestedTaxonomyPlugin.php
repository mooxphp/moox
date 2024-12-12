<?php

declare(strict_types=1);

namespace Moox\Builder;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Builder\Resources\NestedTaxonomyResource;

class NestedTaxonomyPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'nested-taxonomy';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            NestedTaxonomyResource::class,
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
