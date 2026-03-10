<?php

namespace Moox\Core\Installer\Installers;

use Illuminate\Support\Facades\File;
use Moox\Core\Installer\AbstractAssetInstaller;

use function Moox\Prompts\info;
use function Moox\Prompts\note;

/**
 * Installer for translation files.
 * Uses vendor:publish to publish translations from packages.
 */
class TranslationInstaller extends AbstractAssetInstaller
{
    public function getType(): string
    {
        return 'translations';
    }

    public function getLabel(): string
    {
        return 'Translations';
    }

    protected function getMooxInfoKey(): string
    {
        return 'translations';
    }

    protected function getDefaultConfig(): array
    {
        return array_merge(parent::getDefaultConfig(), [
            'priority' => 30,
        ]);
    }

    public function hasItemSelection(): bool
    {
        return false;
    }

    public function checkExists(string $packageName, array $items): bool
    {
        $packageTag = str_replace('moox/', '', $packageName);
        $langPath = lang_path('vendor/'.$packageTag);

        if (File::isDirectory($langPath) && ! empty(File::allFiles($langPath))) {
            return true;
        }

        return false;
    }

    public function install(array $assets): bool
    {
        $selectedAssets = $this->selectItems($assets);

        if (empty($selectedAssets)) {
            note('⏩ No translations selected, skipping');

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

            if ($this->config['skip_existing'] && $this->checkExists($packageName, $items)) {
                $skippedPackages[] = $packageName;
                note("  ℹ️ {$packageName}: translations already exist, skipping");

                continue;
            }

            note("    Publishing translations for {$packageName}...");
            if ($this->publishPackageAssets($packageName, 'translations', $asset)) {
                $published = true;
                $publishedPackages[] = $packageName;
                note('    ✅ Published');
            } else {
                note('    ⚠️ No publish tag found');
            }
        }

        // Show summary
        if ($published) {
            info('✅ Translations published for: '.implode(', ', $publishedPackages));
        }

        if (! empty($skippedPackages)) {
            note('ℹ️ Skipped (already exist): '.implode(', ', $skippedPackages));
        }

        return $published || ! empty($skippedPackages);
    }
}
