<?php

namespace Moox\Core\Installer\Traits;

use Moox\Core\Installer\Contracts\AssetInstallerInterface;
use Moox\Core\Installer\InstallerRegistry;

/**
 * Trait for commands that want to add custom installers.
 *
 * Use this trait in your install command to register package-specific
 * installers that will be processed alongside the default installers.
 *
 * Example:
 * ```php
 * class MyPackageInstallCommand extends Command
 * {
 *     use HasCustomInstallers;
 *
 *     protected function getCustomInstallers(): array
 *     {
 *         return [
 *             new MyCustomInstaller(),
 *         ];
 *     }
 * }
 * ```
 */
trait HasCustomInstallers
{
    /**
     * Get custom installers to register.
     * Override this method to provide package-specific installers.
     *
     * @return array<AssetInstallerInterface>
     */
    protected function getCustomInstallers(): array
    {
        return [];
    }

    /**
     * Register custom installers with the registry.
     */
    protected function registerCustomInstallers(InstallerRegistry $registry): void
    {
        foreach ($this->getCustomInstallers() as $installer) {
            $registry->register($installer->getType(), $installer);
        }
    }

    /**
     * Configure the registry before installation.
     * Override to customize registry behavior.
     */
    protected function configureRegistry(InstallerRegistry $registry): void
    {
        // Override in your command to customize
    }
}
