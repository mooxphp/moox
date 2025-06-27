<?php

namespace Moox\Monorepo\Commands\Concerns;

use Illuminate\Support\Facades\Http;

trait HasPackageVersions
{
    /**
     * Get package versions from Packagist API
     */
    protected function getPackageVersions(array $packages): array
    {
        $tableData = [];

        foreach ($packages as $package) {
            $url = "https://repo.packagist.org/p2/{$package}.json";
            $response = Http::acceptJson()->get($url);

            if ($response->failed()) {
                $tableData[] = [
                    'Package' => $package,
                    'Version' => '/',
                    'Homepage' => "Error: HTTP {$response->status()}",
                ];

                continue;
            }

            $data = $response->json();

            if (! isset($data['packages'][$package])) {
                $tableData[] = [
                    'Package' => $package,
                    'Version' => '/',
                    'Homepage' => 'No versions found',
                ];

                continue;
            }

            $packageData = $data['packages'][$package];
            $latestVersion = reset($packageData); // Get the first (latest) version

            $homepage = $latestVersion['homepage'] ?? 'N/A';
            $version = $latestVersion['version'] ?? 'N/A';

            $tableData[] = [
                'Package' => $package,
                'Version' => $version,
                'Homepage' => $homepage,
            ];
        }

        return $tableData;
    }

    /**
     * Get packages from Packagist vendor API
     */
    protected function getVendorPackages(string $vendor = 'moox'): array
    {
        $url = "https://packagist.org/packages/list.json?vendor={$vendor}";
        $response = Http::acceptJson()->get($url);
        
        if ($response->failed()) {
            $this->error("Could not fetch packages for vendor '{$vendor}' (HTTP {$response->status()})");
            return [];
        }

        $data = $response->json();
        
        if (!is_array($data) || !isset($data['packageNames'])) {
            $this->warn("No packages found for vendor '{$vendor}'");
            return [];
        }

        return $data['packageNames'];
    }

    /**
     * Get Moox packages from local packages directory and Packagist
     */
    protected function getMooxPackages(): array
    {
        return $this->getVendorPackages('moox');
    }

    /**
     * Display package versions in a table
     */
    protected function displayPackageVersions(array $packages): void
    {
        $this->info('Fetching versions for packages...');
        $this->newLine();

        $tableData = $this->getPackageVersions($packages);

        if (! empty($tableData)) {
            $this->table(['Package', 'Version', 'Homepage'], $tableData);
        } else {
            $this->warn('No packages found to display.');
        }
    }
}
