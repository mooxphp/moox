<?php

namespace Moox\Monorepo\Services;

use Illuminate\Support\Collection;

class DevlogService
{
    protected string $devlogPath;

    protected GitHubService $githubService;

    public function __construct(GitHubService $githubService, ?string $devlogPath = null)
    {
        $this->devlogPath = $devlogPath ?? base_path('packages/monorepo/DEVLOG.md');
        $this->githubService = $githubService;
    }

    /**
     * Parse the DEVLOG.md file and return commits grouped by package
     */
    public function parseDevlog(): array
    {
        if (! file_exists($this->devlogPath)) {
            return [];
        }

        $content = file_get_contents($this->devlogPath);
        $lines = explode("\n", $content);

        $commits = [];
        $currentPackage = null;

        foreach ($lines as $line) {
            if (preg_match('/^##\s+(.*)$/', $line, $matches)) {
                $currentPackage = trim($matches[1]);
                if (! isset($commits[strtolower($currentPackage)])) {
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
            if (! isset($devlog[$packageKey])) {
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
        foreach ($allPackages as $package => $packageInfo) {
            $packageKey = strtolower($package);

            if (isset($devlogCommits[$packageKey])) {
                // If package has existing messages in packageInfo, merge with devlog messages
                $result[$package] = array_merge($packageInfo, [
                    'release-message' => $devlogCommits[$packageKey],
                ]);
            } else {
                if (isset($packageInfo['minimum-stability']) && $packageInfo['minimum-stability'] === 'init') {
                    $result[$package] = array_merge($packageInfo, [
                        'release-message' => ['Initial release'],
                    ]);

                    continue;
                }
                $result[$package] = array_merge($packageInfo, [
                    'release-message' => ['Compatibility release'],
                ]);
            }
        }

        return $result;
    }

    /**
     * Process packages for release: get devlinked packages + new packages with commit messages
     */
    public function processAllPackagesForRelease(array $packages): array
    {
        return $this->getAllPackagesWithMessages($packages);
    }

    /**
     * Sort packages by putting compatibility releases at the bottom
     * Returns array formatted for table display with [Package, Messages] columns
     */
    public function sortPackagesForTable(array $packagesWithMessages): array
    {
        return collect($packagesWithMessages)
            ->sortBy(function ($packageInfo) {
                // Sort packages with only "Compatibility release" to bottom
                $messages = $packageInfo['release-message'];

                return count($messages) === 1 && $messages[0] === 'Compatibility release' ? 1 : 0;
            })
            ->map(function ($packageInfo, $package) {
                $stability = $packageInfo['minimum-stability'] ?? '';
                $visibility = $packageInfo['visibility'] ?? 'error';
                return [
                    $package,
                    implode("\n", $packageInfo['release-message']),
                    $stability,
                    $visibility,
                ];
            })
            ->toArray();
    }
}
