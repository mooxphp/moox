<?php

namespace Moox\Core\Installer;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Moox\Core\Installer\Contracts\AssetInstallerInterface;

use function Moox\Prompts\multiselect;
use function Moox\Prompts\note;

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
            scroll: min(10, count($itemOptions)),
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
     *
     * @param  string  $packageName  The composer package name (e.g., "moox/prompts")
     * @param  string  $type  The asset type (e.g., "config", "migrations")
     * @param  string|null  $publishTag  The exact publish tag from mooxInfo (e.g., "moox-prompts-config")
     */
    protected function publishPackageAssets(string $packageName, string $type, ?string $publishTag = null): bool
    {
        $tags = [];

        // If we have the exact tag from mooxInfo, use it first
        if ($publishTag) {
            $tags[] = $publishTag;
        }

        // Fallback tags if the exact tag doesn't work
        $shortName = str_replace('moox/', '', $packageName);
        $spatiePackageName = str_replace('/', '-', $packageName);

        $tags = array_merge($tags, [
            $spatiePackageName.'-'.$type,
            $spatiePackageName,
            $shortName.'-'.$type,
            $shortName,
        ]);

        // Remove duplicates
        $tags = array_unique($tags);

        $published = false;
        foreach ($tags as $tag) {
            try {
                $result = Artisan::call('vendor:publish', [
                    '--tag' => $tag,
                    '--force' => $this->config['force'] ?? false,
                ]);

                $output = trim(Artisan::output());

                if ($result === 0 && ! str_contains($output, 'Nothing to publish')) {
                    $published = true;
                    break;
                } else {
                    note($output);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $published;
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
