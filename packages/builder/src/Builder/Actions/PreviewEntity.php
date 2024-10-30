<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Actions;

use Illuminate\Support\Facades\Artisan;
use Moox\Builder\Builder\Generators\ResourceGenerator;

class PreviewEntity
{
    protected string $entityName;

    protected string $entityNamespace;

    protected string $entityPath;

    public function __construct(
        string $entityName,
        string $entityNamespace,
        string $entityPath
    ) {
        $this->entityName = $entityName;
        $this->entityNamespace = $entityNamespace;
        $this->entityPath = $entityPath;
    }

    public function execute(): void
    {
        // Generate resource in Preview namespace
        (new ResourceGenerator(
            $this->entityName,
            'App\\Preview',
            app_path('Preview'),
            [],
            []
        ))->generate();

        if ($this->isPackageContext()) {
            $this->publishMigrations();
        }

        $this->runMigrations();
    }

    protected function isPackageContext(): bool
    {
        return str_contains($this->entityNamespace, '\\');
    }

    protected function publishMigrations(): void
    {
        $packageName = explode('\\', $this->entityNamespace)[0];
        Artisan::call('vendor:publish', [
            '--provider' => $this->entityNamespace.'\\Providers\\'.$packageName.'ServiceProvider',
            '--tag' => 'migrations',
        ]);
    }

    protected function runMigrations(): void
    {
        Artisan::call('migrate');
    }
}
