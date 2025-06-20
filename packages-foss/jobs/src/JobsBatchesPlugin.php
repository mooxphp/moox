<?php

namespace Moox\Jobs;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Jobs\Resources\JobBatchesResource;

class JobsBatchesPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'filament-job-batches';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            JobBatchesResource::class,
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
