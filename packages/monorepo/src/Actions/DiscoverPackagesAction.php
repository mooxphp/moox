<?php

namespace Moox\Monorepo\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Moox\Monorepo\Contracts\GitHubClientInterface;
use Moox\Monorepo\Contracts\PackageDiscoveryInterface;
use Moox\Monorepo\DataTransferObjects\PackageInfo;

class DiscoverPackagesAction implements PackageDiscoveryInterface
{
    public function __construct(
        private GitHubClientInterface $githubClient
    ) {}

    /**
     * Discover packages by fetching repositories from GitHub and ensuring local folders exist
     */
    public function discoverPackages(string $path, string $visibility = 'public'): Collection
    {
        $organization = config('monorepo.github.organization');
        $repoName = $visibility === 'public'
            ? config('monorepo.github.public_repo')
            : config('monorepo.github.private_repo');

        if (! $repoName) {
            return collect();
        }

        // Get packages from GitHub monorepo
        $remotePackages = $this->githubClient->getMonorepoPackages($organization, $repoName, 'packages');

        if ($remotePackages->isEmpty()) {
            return collect();
        }

        $localPath = base_path($path);

        return $remotePackages->map(function ($remotePackage) use ($localPath, $path, $visibility) {
            $packageName = $remotePackage['name'];
            $localPackagePath = $localPath.'/'.$packageName;

            // Ensure local directory exists
            if (! File::isDirectory($localPackagePath)) {
                $this->createLocalPackageDirectory($localPackagePath, $packageName);
            }

            // Create package info from remote data
            $composerData = $remotePackage['composer'] ?? [];
            $description = $composerData['description'] ?? null;
            $stability = $remotePackage['stability'] ?? 'dev';

            return new PackageInfo(
                name: $packageName,
                path: $path,
                visibility: $visibility,
                stability: $stability,
                description: $description,
                composer: $composerData
            );
        });
    }

    /**
     * Create local package directory
     */
    private function createLocalPackageDirectory(string $path, string $packageName): void
    {
        try {
            File::makeDirectory($path, 0755, true);

            // Optionally create a basic README to indicate this was auto-created
            $readmePath = $path.'/README.md';
            if (! File::exists($readmePath)) {
                File::put($readmePath, "# {$packageName}\n\nAuto-created package directory for GitHub repository sync.\n");
            }
        } catch (\Exception $e) {
            // Log error but don't fail the discovery process
            logger()->warning("Failed to create directory for package {$packageName}: ".$e->getMessage());
        }
    }

    /**
     * Compare local packages with remote repositories (now they should all exist)
     */
    public function compareWithRemote(Collection $localPackages, string $organization): Collection
    {
        // Since we're sourcing from GitHub, all packages should exist in organization
        return $localPackages->map(function (PackageInfo $package) {
            return $package->with(['existsInOrganization' => true]);
        });
    }

    /**
     * Get package information by name
     */
    public function getPackageInfo(string $packageName, string $path, string $visibility = 'public'): ?PackageInfo
    {
        $composerPath = base_path("{$path}/{$packageName}/composer.json");

        if (! File::exists($composerPath)) {
            return null;
        }

        try {
            $composer = json_decode(File::get($composerPath), true);

            if (! $composer) {
                return null;
            }

            return PackageInfo::fromComposer($packageName, $path, $composer, $visibility);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Check if a package exists in the organization
     */
    public function existsInOrganization(string $packageName, string $organization): bool
    {
        return $this->githubClient->getRepository($organization, $packageName) !== null;
    }
}
