<?php

namespace Moox\Core\Installer\Traits;

use Moox\Core\Installer\InstallerRegistry;

use function Moox\Prompts\note;
use function Moox\Prompts\warning;

/**
 * Trait for adding lifecycle hooks to the installation process.
 *
 * Use this trait to add custom logic before/after installation
 * of specific asset types or the entire installation process.
 *
 * Example:
 * ```php
 * class MyInstallCommand extends Command
 * {
 *     use HasInstallationHooks;
 *
 *     protected function beforeInstall(): void
 *     {
 *         $this->info('Starting installation...');
 *     }
 *
 *     protected function afterMigrations(): void
 *     {
 *         // Run custom migrations or setup
 *     }
 * }
 * ```
 */
trait HasInstallationHooks
{
    /**
     * Called before the installation process begins.
     */
    protected function beforeInstall(): void
    {
        // Override to add pre-installation logic
    }

    /**
     * Called after the installation process completes.
     */
    protected function afterInstall(): void
    {
        // Override to add post-installation logic
    }

    /**
     * Called before a specific installer runs.
     */
    protected function beforeInstaller(string $type): void
    {
        // Check for type-specific hook method
        $method = 'before'.ucfirst($type);
        if (method_exists($this, $method)) {
            $this->$method();
        }
    }

    /**
     * Called after a specific installer completes.
     */
    protected function afterInstaller(string $type): void
    {
        // Check for type-specific hook method
        $method = 'after'.ucfirst($type);
        if (method_exists($this, $method)) {
            $this->$method();
        }
    }

    /**
     * Run installation with hooks.
     *
     * Uses graceful degradation - if one installer fails, continues with the next.
     */
    protected function runWithHooks(InstallerRegistry $registry, array $assets, array $selectedTypes): void
    {
        $this->beforeInstall();

        $failedInstallers = [];

        foreach ($selectedTypes as $type) {
            try {
                $installer = $registry->get($type);
                if (! $installer) {
                    note("ℹ️ Installer '{$type}' not found, skipping");

                    continue;
                }

                $typeAssets = $this->filterAssetsByType($assets, $type, $installer);
                if (empty($typeAssets)) {
                    note("ℹ️ No assets for '{$type}', skipping");

                    continue;
                }

                $this->beforeInstaller($type);

                // Setze das Command-Objekt, damit Installer $this->command->call() verwenden können
                // Das ist wichtig, damit der IO-Context nach Prompts korrekt funktioniert
                if (method_exists($installer, 'setCommand')) {
                    $installer->setCommand($this);
                }

                $installer->install($typeAssets);
                $this->afterInstaller($type);
            } catch (\Exception $e) {
                $failedInstallers[] = $type;
                warning("⚠️ Installer '{$type}' failed: {$e->getMessage()}");
                // Continue with next installer instead of stopping
            }
        }

        if (! empty($failedInstallers)) {
            warning('⚠️ Some installers failed: '.implode(', ', $failedInstallers));
        }

        $this->afterInstall();
    }

    /**
     * Filter assets for a specific installer type.
     *
     * This default implementation looks for assets stored with the type as key.
     * Override in your command for custom filtering logic.
     *
     * Expected $assets structure:
     * [
     *     'package/name' => [
     *         'provider' => 'Provider\Class',
     *         'migrations' => ['migration1', 'migration2'],
     *         'configs' => ['config1'],
     *         'publishTags' => [...],
     *     ],
     * ]
     */
    protected function filterAssetsByType(array $assets, string $type, $installer): array
    {
        $typeAssets = [];

        foreach ($assets as $packageName => $packageAssets) {
            // Skip if this package doesn't have assets for this type
            if (! isset($packageAssets[$type]) || empty($packageAssets[$type])) {
                continue;
            }

            $data = $packageAssets[$type];

            // Ensure data is an array
            if (! is_array($data)) {
                $data = [$data];
            }

            $typeAssets[] = [
                'package' => $packageName,
                'data' => $data,
                'provider' => $packageAssets['provider'] ?? null,
                'publishTags' => $packageAssets['publishTags'] ?? [],
            ];
        }

        return $typeAssets;
    }
}
