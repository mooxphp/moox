<?php

namespace Moox\Core\Installer\Installers;

use Illuminate\Support\Facades\File;
use Moox\Core\Installer\AbstractAssetInstaller;

use function Moox\Prompts\info;
use function Moox\Prompts\note;

/**
 * Installer for configuration files.
 * Uses vendor:publish to publish configs from packages.
 */
class ConfigInstaller extends AbstractAssetInstaller
{
    public function getType(): string
    {
        return 'configs';
    }

    public function getLabel(): string
    {
        return 'Config Files';
    }

    protected function getMooxInfoKey(): string
    {
        return 'configFiles';
    }

    protected function getDefaultConfig(): array
    {
        return array_merge(parent::getDefaultConfig(), [
            'priority' => 20,
        ]);
    }

    public function hasItemSelection(): bool
    {
        return false;
    }

    public function checkExists(string $packageName, array $items): bool
    {
        foreach ($items as $configFile) {
            // Handle both array format and string format
            $configName = is_array($configFile) ? ($configFile['name'] ?? '') : $configFile;
            $configPath = config_path($configName.'.php');

            if (File::exists($configPath)) {
                return true;
            }
        }

        return false;
    }

    public function install(array $assets): bool
    {
        $selectedAssets = $this->selectItems($assets);

        if (empty($selectedAssets)) {
            note('⏩ No configs selected, skipping');

            return true;
        }

        info("📦 Installing {$this->getLabel()}...");

        $published = false;
        $publishedPackages = [];
        $skippedPackages = [];

        foreach ($selectedAssets as $asset) {
            $packageName = $asset['package'];
            $items = $asset['data'] ?? [];

            note("  → Processing {$packageName}...");

            // Normalize items to strings for checking
            $normalizedItems = [];
            foreach ($items as $item) {
                if (is_array($item)) {
                    $normalizedItems[] = $item['name'] ?? '';
                } else {
                    $normalizedItems[] = $item;
                }
            }

            if ($this->config['skip_existing'] && $this->checkExists($packageName, $normalizedItems)) {
                $skippedPackages[] = $packageName;
                note("  ℹ️ {$packageName}: configs already exist, skipping");

                continue;
            }

            note("    Publishing configs for {$packageName}...");
            if ($this->publishPackageAssets($packageName, 'config', $asset)) {
                $published = true;
                $publishedPackages[] = $packageName;
                note('    ✅ Published');
            }
        }

        // Show summary
        if ($published) {
            info('✅ Configs published for: '.implode(', ', $publishedPackages));
        }

        if (! empty($skippedPackages)) {
            note('ℹ️ Skipped (already exist): '.implode(', ', $skippedPackages));
        }

        return $published || ! empty($skippedPackages);
    }
}
