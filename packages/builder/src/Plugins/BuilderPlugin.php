<?php

declare(strict_types=1);

namespace Moox\Builder\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Builder\Http\Middleware\ResolveBuilderAdminLocale;
use Moox\Builder\Resources\FieldGroupResource;

class BuilderPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'builder';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            FieldGroupResource::class,
        ]);

        $panel->middleware([
            ResolveBuilderAdminLocale::class,
        ], isPersistent: true);
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
