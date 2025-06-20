<?php

namespace Moox\PressTrainings;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\PressTrainings\Resources\WpTrainingsTopicResource;

class WpTrainingsTopicPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'wp-trainings-topic';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            WpTrainingsTopicResource::class,
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
