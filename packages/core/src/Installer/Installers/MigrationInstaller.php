<?php

namespace Moox\Core\Installer\Installers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Moox\Core\Installer\AbstractAssetInstaller;

use function Moox\Prompts\info;
use function Moox\Prompts\note;
use function Moox\Prompts\warning;

/**
 * Installer for database migrations.
 * Uses vendor:publish to publish migrations from packages.
 */
class MigrationInstaller extends AbstractAssetInstaller
{
    public function getType(): string
    {
        return 'migrations';
    }

    public function getLabel(): string
    {
        return 'Migrations';
    }

    protected function getMooxInfoKey(): string
    {
        return 'migrations';
    }

    protected function getDefaultConfig(): array
    {
        return array_merge(parent::getDefaultConfig(), [
            'priority' => 10,
            'run_after_publish' => true,
        ]);
    }

    public function checkExists(string $packageName, array $items): bool
    {
        if (empty($items)) {
            return false;
        }

        $migrationPath = database_path('migrations');

        if (! File::isDirectory($migrationPath)) {
            return false;
        }

        $existingFiles = File::files($migrationPath);

        foreach ($items as $migrationName) {
            // Handle both string and array formats
            if (is_array($migrationName)) {
                $migrationName = $migrationName['name'] ?? '';
            }

            foreach ($existingFiles as $file) {
                $filename = $file->getFilename();
                // Remove timestamp prefix if exists (format: YYYY_MM_DD_HHMMSS_name.php)
                $nameWithoutTimestamp = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);
                $nameWithoutExtension = str_replace('.php', '', $nameWithoutTimestamp);

                if ($nameWithoutExtension === $migrationName || str_ends_with($nameWithoutExtension, '_'.$migrationName)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function install(array $assets): bool
    {
        $selectedAssets = $this->selectItems($assets);

        if (empty($selectedAssets)) {
            note('⏩ No migrations selected, skipping');

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
                note("  ℹ️ {$packageName}: migrations already exist, skipping");

                continue;
            }

            note("    Publishing migrations for {$packageName}...");
            if ($this->publishPackageAssets($packageName, 'migrations')) {
                $published = true;
                $publishedPackages[] = $packageName;
                note('    ✅ Published');
            } else {
                note('    ⚠️ No publish tag found');
            }
        }

        // Show summary
        if ($published) {
            info('✅ Migrations published for: '.implode(', ', $publishedPackages));
        }

        if (! empty($skippedPackages)) {
            note('ℹ️ Skipped (already exist): '.implode(', ', $skippedPackages));
        }

        // Run migrations if configured
        if ($published && ($this->config['run_after_publish'] ?? true)) {
            $this->runMigrations();
        }

        return $published || ! empty($skippedPackages);
    }

    protected function runMigrations(): void
    {
        try {
            info('🔄 Running migrations...');
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());
            if (! empty($output)) {
                note($output);
            }
            info('✅ Migrations executed successfully');
        } catch (\Exception $e) {
            warning("⚠️ Migration error: {$e->getMessage()}");
        }
    }
}
