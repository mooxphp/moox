<?php

namespace Moox\Devlink\Console\Traits;

trait Check
{
    private array $config;

    private function check(): void
    {
        //
    }

    private function checks(): void
    {
        $this->line("\nConfiguration:");
        $this->line('Base paths:');
        if (empty($this->basePaths)) {
            $this->warn('- No base paths configured');
        } else {
            foreach ($this->basePaths as $path) {
                $resolvedPath = $this->resolvePath($path);
                $this->line("- $path");
                $this->line("  → $resolvedPath".(is_dir($resolvedPath) ? ' (exists)' : ' (not found)'));
            }
        }

        $this->line("\nConfigured packages:");
        if (empty($this->packages)) {
            $this->warn('No packages configured in config/devlink.php');
        } else {
            foreach ($this->packages as $package) {
                $this->line("- $package");
            }
        }

        $this->line('');
    }

    private function resolvePath(string $path): string
    {
        return str_starts_with($path, '~/') ? str_replace('~', getenv('HOME'), $path) : rtrim(realpath($path) ?: $path, '/');
    }

    private function readConfig(): array
    {
        $this->config = config('devlink');

        return $this->config;
    }

    private function getPackagesConfig(): array
    {
        return $this->config['packages'] ?? [];
    }

    private function getPackagesPath(): string
    {
        return $this->config['packages_path'] ?? 'packages';
    }

    private function getMooxBasePath(): string
    {
        return $this->config['moox_base_path'] ?? '../moox';
    }

    private function getMooxproBasePath(): string
    {
        return $this->config['mooxpro_base_path'] ?? '../mooxpro';
    }

    private function checkPackagesPath(): bool
    {
        if (! is_dir($this->getPackagesPath())) {
            return false;
        }

        return true;
    }

    private function checkMooxBasePath(): bool
    {
        if (! is_dir($this->getMooxBasePath())) {
            return false;
        }

        return true;
    }

    private function checkMooxproBasePath(): bool
    {
        if (! is_dir($this->getMooxproBasePath())) {
            return false;
        }

        return true;
    }

    private function checkComposerJson(): bool
    {
        if (! file_exists(base_path('composer.json'))) {
            return false;
        }

        return true;
    }

    private function checkComposerOriginal(): bool
    {
        if (! file_exists(base_path('composer.json-original'))) {
            return false;
        }

        return true;
    }

    private function checkComposerDeploy(): bool
    {
        if (! file_exists(base_path('composer.json-deploy'))) {
            return false;
        }

        return true;
    }
}
