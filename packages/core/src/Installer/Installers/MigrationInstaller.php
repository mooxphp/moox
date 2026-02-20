<?php

namespace Moox\Core\Installer\Installers;

use Illuminate\Support\Facades\File;
use Moox\Core\Installer\AbstractAssetInstaller;
use Moox\Core\Support\PackageDependencyGraph;
use Symfony\Component\Console\Input\StringInput;

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

        // Sort selected assets according to the package dependency graph.
        // If anything goes wrong here, fall back to the original order so
        // publishing still works as before.
        try {
            $selectedAssets = $this->sortAssetsByPackageDependencies($selectedAssets);
        } catch (\Throwable $e) {
            note('ℹ️ Failed to sort migrations by dependency graph: '.$e->getMessage());
        }

        if (empty($selectedAssets)) {
            note('⏩ No migrations selected, skipping');

            return true;
        }

        info("📦 Installing {$this->getLabel()}...");

        $published = false;
        $publishedPackages = [];
        $skippedPackages = [];
        $migrationPath = database_path('migrations');
        $maxTimestampSoFar = $this->getMaxMigrationTimestampInPath($migrationPath);

        foreach ($selectedAssets as $packageIndex => $asset) {
            $packageName = $asset['package'];
            $items = $asset['data'] ?? [];
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

            $existingFilenames = $this->getMigrationFilenames($migrationPath);

            if ($this->publishPackageAssets($packageName, 'migrations', $asset)) {
                $published = true;
                $publishedPackages[] = $packageName;
                $this->assignMigrationTimestampsByOrder($migrationPath, $existingFilenames, $maxTimestampSoFar);
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

    /**
     * Sort assets so that packages with dependencies are processed after
     * the packages they depend on.
     */
    protected function sortAssetsByPackageDependencies(array $assets): array
    {
        if (empty($assets)) {
            return $assets;
        }

        /** @var PackageDependencyGraph $graph */
        $graph = app(PackageDependencyGraph::class);

        $orderedPackages = $graph->getTopologicallySortedPackages();

        if (empty($orderedPackages)) {
            return $assets;
        }

        $index = [];

        foreach ($orderedPackages as $i => $packageName) {
            $index[$packageName] = $i;
        }

        usort($assets, static function (array $a, array $b) use ($index): int {
            $packageA = $a['package'] ?? '';
            $packageB = $b['package'] ?? '';

            $posA = $index[$packageA] ?? PHP_INT_MAX;
            $posB = $index[$packageB] ?? PHP_INT_MAX;

            return $posA <=> $posB;
        });

        return $assets;
    }

    /**
     * Return list of migration filenames in the given path.
     *
     * @return string[]
     */
    protected function getMigrationFilenames(string $path): array
    {
        if (! File::isDirectory($path)) {
            return [];
        }

        $files = File::files($path);

        return array_map(static fn (\SplFileInfo $f) => $f->getFilename(), $files);
    }

    /**
     * Only when there is overlap with other packages: assign an offset to the new migrations
     * so that they run after the previous ones. Within a package, Laravel timestamps
     * remain unchanged (no sorting, no reassignment).
     */
    protected function assignMigrationTimestampsByOrder(string $migrationPath, array $existingFilenames, int &$maxTimestampSoFar): void
    {
        $currentFilenames = $this->getMigrationFilenames($migrationPath);
        $newFilenames = array_values(array_diff($currentFilenames, $existingFilenames));

        if (empty($newFilenames)) {
            return;
        }

        $timestamps = [];
        foreach ($newFilenames as $name) {
            if (preg_match('/^\d{4}_\d{2}_\d{2}_(\d{6})_/', $name, $m)) {
                $timestamps[$name] = (int) $m[1];
            }
        }

        if (empty($timestamps)) {
            return;
        }

        $minNew = min($timestamps);
        $maxNew = max($timestamps);

        if ($minNew <= $maxTimestampSoFar) {
            $offset = $maxTimestampSoFar - $minNew + 1;
            $datePrefix = date('Y_m_d');
            foreach ($timestamps as $oldName => $oldTime) {
                $newTime = sprintf('%06d', $oldTime + $offset);
                $newName = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', $datePrefix.'_'.$newTime.'_', $oldName);
                if ($newName === $oldName) {
                    continue;
                }
                $oldPath = $migrationPath.DIRECTORY_SEPARATOR.$oldName;
                $newPath = $migrationPath.DIRECTORY_SEPARATOR.$newName;
                if (File::exists($oldPath) && ! File::exists($newPath)) {
                    File::move($oldPath, $newPath);
                }
            }
            $maxTimestampSoFar = $maxNew + $offset;
        } else {
            $maxTimestampSoFar = $maxNew;
        }
    }

    /**
     * Get the highest migration timestamp (HHMMSS) in the folder.
     */
    protected function getMaxMigrationTimestampInPath(string $path): int
    {
        $filenames = $this->getMigrationFilenames($path);
        $max = 0;
        foreach ($filenames as $name) {
            if (preg_match('/^\d{4}_\d{2}_\d{2}_(\d{6})_/', $name, $m)) {
                $t = (int) $m[1];
                if ($t > $max) {
                    $max = $t;
                }
            }
        }

        return $max;
    }

    protected function runMigrations(): void
    {
        try {
            info('🔄 Running migrations...');

            // Use $this->command->call() if available (better with Prompts)
            // This uses the correct IO-Context from the Command
            if ($this->command) {
                $this->command->call('migrate', ['--force' => true]);
            } else {
                // Fallback: Directly over Application with clean IO-Context
                $input = new StringInput('migrate --force');
                // Important: Mark as interactive so Prompts work
                $input->setInteractive(true);
                app()->handleCommand($input);
            }

            info('✅ Migrations executed successfully');
        } catch (\Exception $e) {
            warning("⚠️ Migration error: {$e->getMessage()}");
        }
    }
}
