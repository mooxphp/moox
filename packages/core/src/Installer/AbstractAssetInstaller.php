<?php

namespace Moox\Core\Installer;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Moox\Core\Installer\Contracts\AssetInstallerInterface;
use Symfony\Component\Console\Input\StringInput;

use function Moox\Prompts\multiselect;

/**
 * Abstract base class for asset installers.
 *
 * Provides common functionality for all asset installers including:
 * - Configuration management
 * - Item selection UI
 * - Publishing logic
 * - Status reporting
 */
abstract class AbstractAssetInstaller implements AssetInstallerInterface
{
    protected array $config = [];

    protected ?Command $command = null;

    protected bool $enabled = true;

    protected int $priority = 100;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->enabled = $this->config['enabled'] ?? true;
        $this->priority = $this->config['priority'] ?? 100;
    }

    /**
     * Get default configuration for this installer.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => true,
            'priority' => 100,
            'force' => false,
            'skip_existing' => true,
        ];
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        $this->enabled = $this->config['enabled'] ?? $this->enabled;
        $this->priority = $this->config['priority'] ?? $this->priority;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function hasItemSelection(): bool
    {
        return true;
    }

    public function setCommand(?Command $command): self
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Get the mooxInfo key for this installer.
     * Override this if the key differs from the type.
     */
    protected function getMooxInfoKey(): string
    {
        return $this->getType();
    }

    public function getItemsFromMooxInfo(array $mooxInfo): array
    {
        $key = $this->getMooxInfoKey();

        return $mooxInfo[$key] ?? [];
    }

    /**
     * Display item selection and return selected items.
     */
    protected function selectItems(array $assets): array
    {
        if (! $this->hasItemSelection()) {
            return $assets;
        }

        // Collect all items with package info
        $allItems = [];
        $itemToPackageMap = [];

        foreach ($assets as $asset) {
            $packageName = $asset['package'] ?? 'unknown';
            $data = $asset['data'] ?? [];

            // Ensure data is an array
            if (! is_array($data)) {
                continue;
            }

            foreach ($data as $item) {
                // Normalize item to string - skip arrays/objects
                $itemKey = $this->normalizeItemToString($item);
                if ($itemKey === null) {
                    continue;
                }

                $allItems[] = $itemKey;
                $itemToPackageMap[$itemKey] = $packageName;
            }
        }

        if (empty($allItems)) {
            return [];
        }

        // Build multiselect options
        $itemOptions = [];
        foreach ($allItems as $item) {
            $packageName = $itemToPackageMap[$item] ?? 'unknown';
            $itemOptions["{$item} ({$packageName})"] = $item;
        }

        // Let user select
        $selectedLabels = multiselect(
            label: "Select {$this->getType()} to install:",
            options: array_keys($itemOptions),
            default: array_keys($itemOptions),
            scroll: (string) min(10, count($itemOptions)),
            required: false
        );

        // Convert back to items
        $selectedItems = [];
        foreach ($selectedLabels as $label) {
            if (isset($itemOptions[$label])) {
                $selectedItems[] = $itemOptions[$label];
            }
        }

        if (empty($selectedItems)) {
            return [];
        }

        // Filter assets to only include selected items
        $filteredAssets = [];
        foreach ($assets as $asset) {
            $packageName = $asset['package'] ?? 'unknown';
            $data = $asset['data'] ?? [];

            if (! is_array($data)) {
                continue;
            }

            // Normalize data items for comparison
            $normalizedData = [];
            foreach ($data as $item) {
                $normalized = $this->normalizeItemToString($item);
                if ($normalized !== null) {
                    $normalizedData[] = $normalized;
                }
            }

            $filteredData = array_intersect($normalizedData, $selectedItems);

            if (! empty($filteredData)) {
                $filteredAssets[] = [
                    'package' => $packageName,
                    'data' => array_values($filteredData),
                    'provider' => $asset['provider'] ?? null,
                    'publishTags' => $asset['publishTags'] ?? [],
                ];
            }
        }

        return $filteredAssets;
    }

    /**
     * Normalize an item to a string representation.
     * Returns null if the item cannot be normalized.
     */
    protected function normalizeItemToString(mixed $item): ?string
    {
        // Already a string
        if (is_string($item)) {
            return $item;
        }

        // Numeric values
        if (is_int($item) || is_float($item)) {
            return (string) $item;
        }

        // Array with 'name' or 'class' key (common patterns)
        if (is_array($item)) {
            if (isset($item['name'])) {
                return (string) $item['name'];
            }
            if (isset($item['class'])) {
                return (string) $item['class'];
            }
            if (isset($item[0]) && is_string($item[0])) {
                return $item[0];
            }
        }

        // Object with __toString
        if (is_object($item) && method_exists($item, '__toString')) {
            return (string) $item;
        }

        // Cannot normalize - skip this item
        return null;
    }

    /**
     * Publish assets for a package.
     * Tag is taken from $asset['publishTags'][$type], which mooxInfo() fills from
     * the same Package (shortName()) that Spatie uses in publishes().
     *
     * @param  string  $packageName  The composer package name (e.g., "moox/prompts")
     * @param  string  $type  The asset type (e.g., "config", "migrations")
     * @param  array  $asset  Asset array with 'publishTags' from ServiceProvider mooxInfo()
     */
    protected function publishPackageAssets(string $packageName, string $type, array $asset = []): bool
    {
        $publishTag = $asset['publishTags'][$type] ?? null;
        if (! $publishTag) {
            return false;
        }

        try {
            if ($this->command) {
                $result = $this->command->call('vendor:publish', [
                    '--tag' => $publishTag,
                    '--force' => $this->config['force'] ?? false,
                ]);

                return $result === 0;
            }

            $commandString = 'vendor:publish --tag='.escapeshellarg($publishTag);
            if ($this->config['force'] ?? false) {
                $commandString .= ' --force';
            }
            $input = new StringInput($commandString);
            $input->setInteractive(true);

            return app()->handleCommand($input) === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the package path for a given package name.
     */
    protected function getPackagePath(string $packageName): ?string
    {
        $packageParts = explode('/', $packageName);
        $packageDir = $packageParts[1] ?? '';

        $possiblePaths = [
            base_path("packages/{$packageDir}"),
            base_path("vendor/{$packageName}"),
        ];

        foreach ($possiblePaths as $path) {
            if (File::isDirectory($path)) {
                return $path;
            }
        }

        return null;
    }
}
