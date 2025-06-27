<?php

namespace Moox\Monorepo\Console\Commands\Concerns;

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
     * Get Moox packages from config
     */
    protected function getMooxPackages(): array
    {
        $packages = config('devlink.packages');
        $mooxPackages = [];

        foreach ($packages as $name => $package) {
            if ($package['type'] === 'public') {
                $mooxPackages[] = 'moox/'.$name;
            }
        }

        return $mooxPackages;
    }

    /**
     * Display package versions in a table
     */
    protected function displayPackageVersions(array $packages): void
    {
        $this->info('Fetching versions for Moox packages...');
        $this->newLine();

        $tableData = $this->getPackageVersions($packages);

        if (! empty($tableData)) {
            $this->table(['Package', 'Version', 'Homepage'], $tableData);
        } else {
            $this->warn('No packages found to display.');
        }
    }
}
