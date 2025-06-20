<?php

namespace Moox\Training;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Training\Resources\TrainingTypeResource;

class TrainingTypePlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'training-types';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            TrainingTypeResource::class,
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
