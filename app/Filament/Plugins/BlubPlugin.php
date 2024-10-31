<?php

declare(strict_types=1);

namespace App\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use App\Filament\Resources\BlubResource;

class BlubPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'blub';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            BlubResource::class
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
