<?php

namespace Moox\Monorepo\Contracts;

interface VersionManagerInterface
{
    /**
     * Get the current version from a repository
     */
    public function getCurrentVersion(string $organization, string $repository): ?string;

    /**
     * Suggest the next version based on current version
     */
    public function suggestNextVersion(string $currentVersion): string;

    /**
     * Validate version format
     */
    public function validateVersionFormat(string $version): bool;

    /**
     * Compare two versions
     */
    public function compareVersions(string $version1, string $version2): int;

    /**
     * Check if a version is a prerelease
     */
    public function isPrerelease(string $version): bool;

    /**
     * Parse version components
     */
    public function parseVersion(string $version): array;
} 