<?php

namespace Moox\Jobs;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Jobs\Resources\JobsResource;

class JobsPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'filament-jobs';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            JobsResource::class,
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
