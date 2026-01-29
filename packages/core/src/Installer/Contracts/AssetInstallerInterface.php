<?php

namespace Moox\Core\Installer\Contracts;

/**
 * Contract for asset installers.
 *
 * Implement this interface to create custom asset installers
 * that can be registered with the Moox Installer.
 */
interface AssetInstallerInterface
{
    /**
     * Get the unique type identifier for this installer.
     * Used for configuration and registry purposes.
     */
    public function getType(): string;

    /**
     * Get the human-readable label for this installer.
     */
    public function getLabel(): string;

    /**
     * Get the priority for installation order.
     * Lower numbers = higher priority (installed first).
     */
    public function getPriority(): int;

    /**
     * Check if this installer is enabled.
     */
    public function isEnabled(): bool;

    /**
     * Check if assets of this type already exist.
     */
    public function checkExists(string $packageName, array $items): bool;

    /**
     * Install the assets.
     *
     * @param  array  $assets  Array of package assets with structure:
     *                         [['package' => string, 'data' => array, 'provider' => ?string], ...]
     * @return bool True if installation was successful
     */
    public function install(array $assets): bool;

    /**
     * Get items from mooxInfo for this installer type.
     *
     * @param  array  $mooxInfo  The mooxInfo array from the service provider
     * @return array The items relevant to this installer
     */
    public function getItemsFromMooxInfo(array $mooxInfo): array;

    /**
     * Whether this installer requires item-level selection.
     * If true, users can select individual items to install.
     * If false, all items are installed together.
     */
    public function hasItemSelection(): bool;

    /**
     * Get configuration options for this installer.
     */
    public function getConfig(): array;

    /**
     * Set configuration options for this installer.
     */
    public function setConfig(array $config): void;
}
