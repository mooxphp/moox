<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Data\Filament\Resources\StaticDocumentTypeResource;

class StaticDocumentTypePlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'staticdocumenttype';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            StaticDocumentTypeResource::class,
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
