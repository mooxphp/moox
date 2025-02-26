<?php

namespace Moox\Core\Services;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PackageService
{
    public function getInstalledComposerPackages(): array
    {
        return InstalledVersions::getInstalledPackages();
    }

    public function getInstalledLaravelPackages(): array
    {
        $packages = [];

        foreach ($this->getInstalledComposerPackages() as $packageName) {
            $composerFilePath = base_path("vendor/{$packageName}/composer.json");

            if (file_exists($composerFilePath)) {
                $composerData = json_decode(file_get_contents($composerFilePath), true);

                if (isset($composerData['extra']['laravel']['providers'])) {
                    $packages[] = $packageName;
                }
            }
        }

        return $packages;
    }

    public function getInstalledMooxPackages(): array
    {
        $packages = [];

        foreach ($this->getInstalledLaravelPackages() as $packageName) {
            $composerFilePath = base_path("vendor/{$packageName}/composer.json");
            $composerData = json_decode(file_get_contents($composerFilePath), true);

            if (isset($composerData['extra']['laravel']['providers'])) {
                foreach ($composerData['extra']['laravel']['providers'] as $provider) {
                    if (class_exists($provider) && is_subclass_of($provider, \Moox\Core\MooxServiceProvider::class)) {
                        $packages[] = $packageName;
                        break;
                    }
                }
            }
        }

        return $packages;
    }

    public function getInstalledMooxPackagesInfo(): array
    {
        $packages = [];

        foreach ($this->getInstalledMooxPackages() as $packageName) {
            $composerFilePath = base_path("vendor/{$packageName}/composer.json");
            $composerData = json_decode(file_get_contents($composerFilePath), true);

            foreach ($composerData['extra']['laravel']['providers'] as $provider) {
                if (class_exists($provider) && is_subclass_of($provider, \Moox\Core\MooxServiceProvider::class)) {
                    $providerInstance = app()->resolveProvider($provider);
                    if ($providerInstance instanceof \Moox\Core\MooxServiceProvider) {
                        $package = new \Spatie\LaravelPackageTools\Package;
                        $providerInstance->configurePackage($package);

                        $packages[$packageName] = [
                            'name' => $packageName,
                            'provider' => $provider,
                            'info' => $providerInstance->mooxInfo(),
                        ];
                    }
                    break;
                }
            }
        }

        return $packages;
    }

    public function getMigrations(array $package): array
    {
        $composerFilePath = base_path("vendor/{$package['name']}/composer.json");
        $composerData = json_decode(file_get_contents($composerFilePath), true);

        return $composerData['extra']['laravel']['migrations'];
    }

    public function getConfig(array $package): array
    {
        $composerFilePath = base_path("vendor/{$package['name']}/composer.json");
        $composerData = json_decode(file_get_contents($composerFilePath), true);

        return $composerData['extra']['laravel']['config'];
    }

    public function getSeeders(array $package): array
    {
        $composerFilePath = base_path("vendor/{$package['name']}/composer.json");
        $composerData = json_decode(file_get_contents($composerFilePath), true);

        return $composerData['extra']['laravel']['seeders'];
    }

    public function getPlugins(array $package): array
    {
        $composerFilePath = base_path("vendor/{$package['name']}/composer.json");
        $composerData = json_decode(file_get_contents($composerFilePath), true);

        return $composerData['extra']['laravel']['plugins'];
    }

    public function checkMigrationStatus(string $migrationPath): array
    {
        $table = $this->getMigrationTable($migrationPath);
        if (! Schema::hasTable($table)) {
            return [
                'hasChanges' => true,
                'hasDataInDeletedFields' => false,
            ];
        }

        $pendingColumns = $this->getPendingColumns($migrationPath, $table);
        $droppingColumns = $this->getDroppingColumns($migrationPath);

        $hasDataInDroppedColumns = false;
        if (! empty($droppingColumns)) {
            foreach ($droppingColumns as $column) {
                $hasData = DB::table($table)
                    ->whereNotNull($column)
                    ->limit(1)
                    ->count() > 0;

                if ($hasData) {
                    $hasDataInDroppedColumns = true;
                    break;
                }
            }
        }

        return [
            'hasChanges' => ! empty($pendingColumns) || ! empty($droppingColumns),
            'hasDataInDeletedFields' => $hasDataInDroppedColumns,
        ];
    }

    private function getMigrationTable(string $migrationPath): string
    {
        $migrationContent = file_get_contents(base_path($migrationPath));
        preg_match('/Schema::create\([\'"]([^\'"]+)[\'"]/i', $migrationContent, $matches);

        if (empty($matches[1])) {
            preg_match('/Schema::table\([\'"]([^\'"]+)[\'"]/i', $migrationContent, $matches);
        }

        return $matches[1] ?? throw new \RuntimeException('Could not determine table name from migration');
    }

    private function getPendingColumns(string $migrationPath, string $table): array
    {
        $migrationContent = file_get_contents(base_path($migrationPath));
        preg_match_all('/\$table->([a-zA-Z]+)\([\'"]([^\'"]+)[\'"]/i', $migrationContent, $matches);

        if (empty($matches[1]) || empty($matches[2])) {
            return [];
        }

        $definedColumns = array_combine($matches[2], $matches[1]);
        $existingColumns = Schema::getColumnListing($table);

        return array_diff_key($definedColumns, array_flip($existingColumns));
    }

    private function getDroppingColumns(string $migrationPath): array
    {
        $migrationContent = file_get_contents(base_path($migrationPath));
        preg_match_all('/dropColumn\([\'"]([^\'"]+)[\'"]/i', $migrationContent, $matches);

        return $matches[1];
    }
}
