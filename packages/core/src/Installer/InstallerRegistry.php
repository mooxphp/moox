<?php

namespace Moox\Core\Installer;

use Moox\Core\Installer\Contracts\AssetInstallerInterface;
use Moox\Core\Installer\Installers\ConfigInstaller;
use Moox\Core\Installer\Installers\MigrationInstaller;
use Moox\Core\Installer\Installers\PluginInstaller;
use Moox\Core\Installer\Installers\SeederInstaller;
use Moox\Core\Installer\Installers\TranslationInstaller;

/**
 * Registry for managing asset installers.
 *
 * This registry provides a centralized way to:
 * - Register custom installers
 * - Configure installer behavior
 * - Enable/disable specific installers
 * - Set installation priority
 */
class InstallerRegistry
{
    /** @var array<string, AssetInstallerInterface> */
    protected array $installers = [];

    /** @var array<string, bool> */
    protected array $skipped = [];

    protected array $globalConfig = [];

    protected static ?InstallerRegistry $instance = null;

    public function __construct(array $config = [])
    {
        $this->globalConfig = $config;
        $this->registerDefaultInstallers();
    }

    /**
     * Get singleton instance.
     */
    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * Reset singleton instance (useful for testing).
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Register the default installers.
     *
     * Installers are only enabled if explicitly defined in the config.
     * If an installer is not in the config, it will be disabled by default.
     */
    protected function registerDefaultInstallers(): void
    {
        $defaults = [
            'migrations' => MigrationInstaller::class,
            'configs' => ConfigInstaller::class,
            'translations' => TranslationInstaller::class,
            'seeders' => SeederInstaller::class,
            'plugins' => PluginInstaller::class,
        ];

        $configuredInstallers = $this->globalConfig['installers'] ?? [];

        foreach ($defaults as $type => $installerClass) {
            // Only enable installer if it's explicitly defined in config
            $isConfigured = array_key_exists($type, $configuredInstallers);
            $config = $configuredInstallers[$type] ?? [];

            // If not in config, disable by default
            if (! $isConfigured) {
                $config['enabled'] = false;
            }

            $this->register($type, new $installerClass($config));
        }
    }

    /**
     * Register an installer.
     */
    public function register(string $type, AssetInstallerInterface $installer): self
    {
        $this->installers[$type] = $installer;

        return $this;
    }

    /**
     * Unregister an installer.
     */
    public function unregister(string $type): self
    {
        unset($this->installers[$type]);

        return $this;
    }

    /**
     * Get an installer by type.
     */
    public function get(string $type): ?AssetInstallerInterface
    {
        return $this->installers[$type] ?? null;
    }

    /**
     * Check if an installer is registered.
     */
    public function has(string $type): bool
    {
        return isset($this->installers[$type]);
    }

    /**
     * Get all registered installers.
     *
     * @return array<string, AssetInstallerInterface>
     */
    public function all(): array
    {
        return $this->installers;
    }

    /**
     * Get all enabled installers sorted by priority.
     *
     * @return array<string, AssetInstallerInterface>
     */
    public function getEnabled(): array
    {
        $enabled = array_filter(
            $this->installers,
            fn (AssetInstallerInterface $installer, string $type) => $installer->isEnabled() && ! ($this->skipped[$type] ?? false),
            ARRAY_FILTER_USE_BOTH
        );

        // Sort by priority (lower = first)
        uasort($enabled, fn ($a, $b) => $a->getPriority() <=> $b->getPriority());

        return $enabled;
    }

    /**
     * Skip an installer type.
     */
    public function skip(string $type): self
    {
        $this->skipped[$type] = true;

        return $this;
    }

    /**
     * Skip multiple installer types.
     */
    public function skipMany(array $types): self
    {
        foreach ($types as $type) {
            $this->skip($type);
        }

        return $this;
    }

    /**
     * Unskip an installer type.
     */
    public function unskip(string $type): self
    {
        unset($this->skipped[$type]);

        return $this;
    }

    /**
     * Check if an installer type is skipped.
     */
    public function isSkipped(string $type): bool
    {
        return $this->skipped[$type] ?? false;
    }

    /**
     * Enable an installer.
     */
    public function enable(string $type): self
    {
        if (isset($this->installers[$type])) {
            $this->installers[$type]->setConfig(['enabled' => true]);
        }

        return $this;
    }

    /**
     * Disable an installer.
     */
    public function disable(string $type): self
    {
        if (isset($this->installers[$type])) {
            $this->installers[$type]->setConfig(['enabled' => false]);
        }

        return $this;
    }

    /**
     * Configure an installer.
     */
    public function configure(string $type, array $config): self
    {
        if (isset($this->installers[$type])) {
            $this->installers[$type]->setConfig($config);
        }

        return $this;
    }

    /**
     * Configure all installers.
     */
    public function configureAll(array $config): self
    {
        foreach ($this->installers as $installer) {
            $installer->setConfig($config);
        }

        return $this;
    }

    /**
     * Set priority for an installer.
     */
    public function setPriority(string $type, int $priority): self
    {
        if (isset($this->installers[$type])) {
            $this->installers[$type]->setConfig(['priority' => $priority]);
        }

        return $this;
    }

    /**
     * Get installer types.
     */
    public function types(): array
    {
        return array_keys($this->installers);
    }

    /**
     * Get labels for all installers.
     */
    public function labels(): array
    {
        $labels = [];
        foreach ($this->installers as $type => $installer) {
            $labels[$type] = $installer->getLabel();
        }

        return $labels;
    }

    /**
     * Only enable specific installer types.
     */
    public function only(array $types): self
    {
        foreach ($this->installers as $type => $installer) {
            if (in_array($type, $types)) {
                $installer->setConfig(['enabled' => true]);
                unset($this->skipped[$type]);
            } else {
                $installer->setConfig(['enabled' => false]);
            }
        }

        return $this;
    }

    /**
     * Enable all except specific types.
     */
    public function except(array $types): self
    {
        foreach ($this->installers as $type => $installer) {
            if (in_array($type, $types)) {
                $installer->setConfig(['enabled' => false]);
            } else {
                $installer->setConfig(['enabled' => true]);
                unset($this->skipped[$type]);
            }
        }

        return $this;
    }

    /**
     * Create a new registry with only specific types.
     */
    public function withOnly(array $types): self
    {
        $new = clone $this;
        $new->only($types);

        return $new;
    }

    /**
     * Create a new registry without specific types.
     */
    public function without(array $types): self
    {
        $new = clone $this;
        $new->except($types);

        return $new;
    }
}
