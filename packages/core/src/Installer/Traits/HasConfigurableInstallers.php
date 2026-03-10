<?php

namespace Moox\Core\Installer\Traits;

use Moox\Core\Installer\InstallerRegistry;

/**
 * Trait for commands that want configurable installer behavior.
 *
 * Use this trait to allow configuration-driven installer setup.
 * Configuration can come from config files, command options, or both.
 *
 * Example config (config/moox-installer.php):
 * ```php
 * return [
 *     'installers' => [
 *         'migrations' => [
 *             'enabled' => true,
 *             'priority' => 10,
 *             'run_after_publish' => true,
 *         ],
 *         'configs' => [
 *             'enabled' => true,
 *             'priority' => 20,
 *         ],
 *         'plugins' => [
 *             'enabled' => true,
 *             'priority' => 100,
 *             'allow_multiple_panels' => true,
 *         ],
 *     ],
 *     'skip' => [],
 *     'only' => [],
 * ];
 * ```
 */
trait HasConfigurableInstallers
{
    /**
     * Get installer configuration.
     * Override to provide custom config source.
     */
    protected function getInstallerConfig(): array
    {
        return config('moox-installer', []);
    }

    /**
     * Apply configuration to registry.
     */
    protected function applyConfiguration(InstallerRegistry $registry): void
    {
        $config = $this->getInstallerConfig();

        // Apply individual installer configs
        $installerConfigs = $config['installers'] ?? [];
        foreach ($installerConfigs as $type => $typeConfig) {
            $registry->configure($type, $typeConfig);
        }

        // Apply skip/only from config
        if (! empty($config['skip'])) {
            $registry->skipMany($config['skip']);
        }

        if (! empty($config['only'])) {
            $registry->only($config['only']);
        }
    }

    /**
     * Build registry from configuration.
     */
    protected function buildConfiguredRegistry(): InstallerRegistry
    {
        $config = $this->getInstallerConfig();
        $registry = new InstallerRegistry($config);
        $this->applyConfiguration($registry);

        return $registry;
    }
}
