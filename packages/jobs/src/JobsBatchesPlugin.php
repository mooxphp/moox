<?php

namespace Moox\Jobs;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Moox\Jobs\Resources\JobBatchesResource;
use Filament\Support\Concerns\EvaluatesClosures;

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
