<?php

namespace Moox\Training;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Training\Resources\TrainingDateResource;

class TrainingDatePlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'training-dates';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            TrainingDateResource::class,
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
