<?php

declare(strict_types=1);

namespace Moox\Audit\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Audit\Resources\AuditResource;
use Moox\Audit\Support\AuditBootstrap;

class AuditPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'audit';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            AuditResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        AuditBootstrap::boot();
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
