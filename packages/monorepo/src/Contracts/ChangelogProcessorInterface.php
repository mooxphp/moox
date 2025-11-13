<?php

namespace Moox\Monorepo\Contracts;

use Illuminate\Support\Collection;

interface ChangelogProcessorInterface
{
    /**
     * Parse changelog file and extract changes by package
     */
    public function parseChangelog(string $changelogPath): Collection;

    /**
     * Get changes for a specific package
     */
    public function getPackageChanges(string $packageName): Collection;

    /**
     * Generate release message for a package
     */
    public function generateReleaseMessage(string $packageName, ?string $stability = null): string;

    /**
     * Check if package has explicit changes
     */
    public function hasExplicitChanges(string $packageName): bool;

    /**
     * Get all packages with their changes
     */
    public function getAllPackagesWithChanges(): Collection;
}
