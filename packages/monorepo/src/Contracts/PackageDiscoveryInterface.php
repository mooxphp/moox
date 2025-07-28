<?php

namespace Moox\Monorepo\Contracts;

use Illuminate\Support\Collection;
use Moox\Monorepo\DataTransferObjects\PackageInfo;

interface PackageDiscoveryInterface
{
    /**
     * Discover all packages in the monorepo
     */
    public function discoverPackages(string $path, string $visibility = 'public'): Collection;

    /**
     * Compare local packages with remote repositories
     */
    public function compareWithRemote(Collection $localPackages, string $organization): Collection;

    /**
     * Get package information by name
     */
    public function getPackageInfo(string $packageName, string $path): ?PackageInfo;

    /**
     * Check if a package exists in the organization
     */
    public function existsInOrganization(string $packageName, string $organization): bool;
}
