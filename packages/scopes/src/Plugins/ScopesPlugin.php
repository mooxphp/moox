<?php

declare(strict_types=1);

namespace Moox\Scopes\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Core\Support\Resources\ResourceNavigationRegistrar;
use Moox\Scopes\Entities\Scopes\ScopeResource;

class ScopesPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'scopes';
    }

    public function register(Panel $panel): void
    {
        ResourceNavigationRegistrar::register($panel, [
            ScopeResource::class,
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
