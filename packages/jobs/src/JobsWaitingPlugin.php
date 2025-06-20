<?php

namespace Moox\Jobs;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Jobs\Resources\JobsWaitingResource;

class JobsWaitingPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'filament-jobs-waiting';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            JobsWaitingResource::class,
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
