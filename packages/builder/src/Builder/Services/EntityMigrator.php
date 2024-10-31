<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Services;

use Illuminate\Support\Facades\Artisan;

class EntityMigrator extends AbstractService
{
    public function execute(): void
    {
        if ($this->context->shouldPublishMigrations()) {
            $this->publishMigrations();
        }

        $this->runMigrations();
    }

    private function publishMigrations(): void
    {
        Artisan::call('vendor:publish', [
            '--provider' => $this->context->getBaseNamespace().'\\ServiceProvider',
            '--tag' => 'migrations',
        ]);
    }

    private function runMigrations(): void
    {
        Artisan::call('migrate');
    }
}
