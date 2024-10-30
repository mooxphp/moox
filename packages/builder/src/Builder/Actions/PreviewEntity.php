<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Actions;

use Illuminate\Support\Facades\Artisan;
use Moox\Builder\Builder\Generators\PanelGenerator;
use Moox\Builder\Builder\Support\PanelRegistrar;

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
        (new PanelGenerator($this->entityName, $this->entityNamespace, $this->entityPath))->generate();

        if ($this->isPackageContext()) {
            $this->publishMigrations();
        }

        $this->runMigrations();

        $panelClass = $this->entityNamespace.'\\Providers\\'.$this->entityName.'PanelProvider';
        PanelRegistrar::register($panelClass);
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
