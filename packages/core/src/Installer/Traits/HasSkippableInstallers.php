<?php

namespace Moox\Core\Installer\Traits;

use Moox\Core\Installer\InstallerRegistry;

/**
 * Trait for commands that allow skipping installers.
 *
 * Use this trait to enable --skip options in your install command.
 *
 * Example signature:
 * ```php
 * protected $signature = 'mypackage:install
 *     {--skip=* : Skip specific installers (migrations, configs, etc.)}
 *     {--only=* : Only run specific installers}';
 * ```
 */
trait HasSkippableInstallers
{
    /**
     * Get installers to skip from command options.
     */
    protected function getSkippedInstallers(): array
    {
        return $this->option('skip');
    }

    /**
     * Get installers to run exclusively from command options.
     */
    protected function getOnlyInstallers(): array
    {
        return $this->option('only');
    }

    /**
     * Apply skip/only options to registry.
     */
    protected function applySkipOptions(InstallerRegistry $registry): void
    {
        $skip = $this->getSkippedInstallers();
        $only = $this->getOnlyInstallers();

        if (! empty($only)) {
            $registry->only($only);
        } elseif (! empty($skip)) {
            $registry->skipMany($skip);
        }
    }

    /**
     * Check if an installer type should be skipped.
     */
    protected function shouldSkip(string $type): bool
    {
        $skip = $this->getSkippedInstallers();
        $only = $this->getOnlyInstallers();

        if (! empty($only)) {
            return ! in_array($type, $only);
        }

        return in_array($type, $skip);
    }
}
