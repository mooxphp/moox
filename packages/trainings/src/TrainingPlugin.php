<?php

namespace Moox\Training;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Training\Resources\TrainingResource;

class TrainingPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'trainings';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            TrainingResource::class,
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
