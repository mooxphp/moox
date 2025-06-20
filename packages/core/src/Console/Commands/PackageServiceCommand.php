<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
use Moox\Core\Services\PackageService;

class PackageServiceCommand extends Command
{
    protected $signature = 'mooxcore:packages';

    protected $description = 'Show installed packages';

    public function handle(PackageService $packageService): void
    {
        $choice = $this->choice(
            'Which packages would you like to see?',
            [
                'All Composer Packages',
                'Laravel Packages',
                'Moox Packages',
                'Moox Packages Info',
            ],
            0,
            null,
            false
        );

        $packages = match ($choice) {
            'All Composer Packages' => $packageService->getInstalledComposerPackages(),
            'Laravel Packages' => $packageService->getInstalledLaravelPackages(),
            'Moox Packages' => $packageService->getInstalledMooxPackages(),
            'Moox Packages Info' => $this->formatMooxInfo($packageService->getInstalledMooxPackagesInfo()),
            default => [],
        };

        if ($choice === 'Moox Packages Info') {
            foreach ($packages as $package) {
                $this->info($package['name']);
                $this->table(
                    ['Type', 'Value'],
                    $package['rows']
                );
                $this->newLine();
            }
        } else {
            $rows = array_map(fn (string $package) => [$package], $packages);
            $this->table(['Package'], $rows);
        }
    }

    protected function formatMooxInfo(array $packagesInfo): array
    {
        $formatted = [];

        foreach ($packagesInfo as $packageName => $info) {
            $formatted[] = [
                'name' => $packageName,
                'rows' => [
                    ['Plugins', is_array($info['info']['plugins']) ? implode(', ', $info['info']['plugins']) : ''],
                    ['First Plugin', $info['info']['firstPlugin'] ? 'Yes' : 'No'],
                    ['Migrations', is_array($info['info']['migrations']) ? implode(', ', $info['info']['migrations']) : ''],
                    ['Seeders', is_array($info['info']['seeders']) ? implode(', ', $info['info']['seeders']) : ''],
                    ['Config Files', is_array($info['info']['configFiles']) ? implode(', ', $info['info']['configFiles']) : ''],
                    ['Translations', is_array($info['info']['translations']) ? implode(', ', $info['info']['translations']) : ''],
                ],
            ];
        }

        return $formatted;
    }
}
