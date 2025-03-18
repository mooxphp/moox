<?php

declare(strict_types=1);

namespace App\Builder\Plugins;

use App\Builder\Resources\SoftDeleteItemResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

class SoftDeleteItemPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'soft-delete-item';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            SoftDeleteItemResource::class,
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
