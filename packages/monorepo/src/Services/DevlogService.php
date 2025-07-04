<?php

namespace Moox\Monorepo\Services;

use Illuminate\Support\Collection;

class DevlogService
{
    protected string $devlogPath;

    public function __construct(string $devlogPath = null)
    {
        $this->devlogPath = $devlogPath ?? base_path('packages/monorepo/DEVLOG.md');
    }

    /**
     * Parse the DEVLOG.md file and return commits grouped by package
     */
    public function parseDevlog(): array
    {
        if (!file_exists($this->devlogPath)) {
            return [];
        }

        $content = file_get_contents($this->devlogPath);
        $lines = explode("\n", $content);

        $commits = [];
        $currentPackage = null;

        foreach ($lines as $line) {
            if (preg_match('/^##\s+(.*)$/', $line, $matches)) {
                $currentPackage = trim($matches[1]);
                if (!isset($commits[strtolower($currentPackage)])) {
                    $commits[strtolower($currentPackage)] = [];
                }
            } elseif ($currentPackage && preg_match('/^-\s+(.*)$/', $line, $matches)) {
                $commits[strtolower($currentPackage)][] = trim($matches[1]);
            }
        }

        return $commits;
    }

    /**
     * Get commit messages for packages, with fallback for packages not in devlog
     */
    public function getCommitMessages(array $packages): Collection
    {
        $devlogCommits = $this->parseDevlog();

        return collect($packages)->mapWithKeys(function ($package) use ($devlogCommits) {
            $packageKey = strtolower($package);
            
            if (isset($devlogCommits[$packageKey])) {
                return [$package => $devlogCommits[$packageKey]];
            }

            return [$package => ['Compatibility release']];
        });
    }

    /**
     * Add entries for new packages to the devlog array (in memory only)
     */
    public function addNewPackageEntries(array $newPackages): array
    {
        $devlog = $this->parseDevlog();

        foreach ($newPackages as $package) {
            $packageKey = strtolower($package);
            if (!isset($devlog[$packageKey])) {
                $devlog[$packageKey] = ['Initial release'];
            }
        }

        return $devlog;
    }

    /**
     * Get all packages with their commit messages, including compatibility releases
     */
    public function getAllPackagesWithMessages(array $allPackages): array
    {
        $devlogCommits = $this->parseDevlog();
        $result = [];

        foreach ($allPackages as $package) {
            $packageKey = strtolower($package);
            
            if (isset($devlogCommits[$packageKey])) {
                $result[$package] = $devlogCommits[$packageKey];
            } else {
                $result[$package] = ['Compatibility release'];
            }
        }

        return $result;
    }

    /**
     * Process packages for release: get devlinked packages + new packages with commit messages
     */
    public function processAllPackagesForRelease(array $newPackages = []): array
    {
        // Get devlinked packages (from monorepo)
        $publicBasePath = config('devlink.public_base_path', '../moox/packages');
        $privateBasePath = config('devlink.private_base_path', 'disabled');

        $devlinkedPackages = collect(array_merge(
            \Illuminate\Support\Facades\File::directories(base_path($publicBasePath)),
            $privateBasePath !== 'disabled' ? \Illuminate\Support\Facades\File::directories(base_path($privateBasePath)) : []
        ))->map(fn ($dir) => basename($dir))
            ->toArray();

        // Get commit messages for devlinked packages
        $devlinkedMessages = $this->getCommitMessages($devlinkedPackages);

        // Add new packages with "Initial release" message
        $result = $devlinkedMessages->toArray();
        if (!empty($newPackages)) {
            foreach ($newPackages as $package) {
                if (!isset($result[$package])) { // Only add if not already in devlinked
                    $result[$package] = ['Initial release'];
                }
            }
        }

        return $result;
    }

    /**
     * Sort packages by putting compatibility releases at the bottom
     * Returns array formatted for table display with [Package, Messages] columns
     */
    public function sortPackagesForTable(array $packagesWithMessages): array
    {
        return collect($packagesWithMessages)
            ->sortBy(function($messages) {
                // Sort packages with only "Compatibility release" to bottom
                return count($messages) === 1 && $messages[0] === 'Compatibility release' ? 1 : 0;
            })
            ->map(function($messages, $package) {
                return [$package, implode("\n", $messages)];
            })
            ->toArray();
    }
}