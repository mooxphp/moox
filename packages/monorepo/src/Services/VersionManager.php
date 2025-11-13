<?php

namespace Moox\Monorepo\Services;

use Moox\Monorepo\Contracts\GitHubClientInterface;
use Moox\Monorepo\Contracts\VersionManagerInterface;

class VersionManager implements VersionManagerInterface
{
    public function __construct(
        private GitHubClientInterface $githubClient
    ) {}

    /**
     * Get the current version from a repository
     */
    public function getCurrentVersion(string $organization, string $repository): ?string
    {
        $tag = $this->githubClient->getLatestReleaseTag($organization, $repository);

        if (! $tag) {
            return null;
        }

        // Remove 'v' prefix if present
        return ltrim($tag, 'v');
    }

    /**
     * Suggest the next version based on current version
     */
    public function suggestNextVersion(string $currentVersion): string
    {
        if ($this->isPrerelease($currentVersion)) {
            return $this->suggestNextPrereleaseVersion($currentVersion);
        }

        return $this->suggestNextStableVersion($currentVersion);
    }

    /**
     * Validate version format
     */
    public function validateVersionFormat(string $version): bool
    {
        // Support semantic versioning with optional prerelease
        // Examples: 1.0.0, 1.0.0-alpha.1, 1.0.0-beta.2, 1.0.0-rc.1
        $pattern = '/^(\d+)\.(\d+)\.(\d+)(?:-(alpha|beta|rc)(?:\.(\d+))?)?$/';

        return preg_match($pattern, $version) === 1;
    }

    /**
     * Compare two versions
     */
    public function compareVersions(string $version1, string $version2): int
    {
        return version_compare($version1, $version2);
    }

    /**
     * Check if a version is a prerelease
     */
    public function isPrerelease(string $version): bool
    {
        return preg_match('/-(?:alpha|beta|rc)(?:\.\d+)?$/', $version) === 1;
    }

    /**
     * Parse version components
     */
    public function parseVersion(string $version): array
    {
        $pattern = '/^(\d+)\.(\d+)\.(\d+)(?:-(alpha|beta|rc)(?:\.(\d+))?)?$/';

        if (! preg_match($pattern, $version, $matches)) {
            throw new \InvalidArgumentException("Invalid version format: {$version}");
        }

        return [
            'major' => (int) $matches[1],
            'minor' => (int) $matches[2],
            'patch' => (int) $matches[3],
            'prerelease' => $matches[4] ?? null,
            'prerelease_version' => isset($matches[5]) ? (int) $matches[5] : null,
            'is_prerelease' => ! empty($matches[4]),
        ];
    }

    /**
     * Suggest next prerelease version
     */
    private function suggestNextPrereleaseVersion(string $currentVersion): string
    {
        $components = $this->parseVersion($currentVersion);

        if ($components['prerelease'] === 'rc') {
            // For RC versions, suggest stable release
            return "{$components['major']}.{$components['minor']}.{$components['patch']}";
        }

        // For alpha/beta, suggest next prerelease increment
        $nextPrereleaseVersion = ($components['prerelease_version'] ?? 0) + 1;

        return "{$components['major']}.{$components['minor']}.{$components['patch']}-{$components['prerelease']}.{$nextPrereleaseVersion}";
    }

    /**
     * Suggest next stable version
     */
    private function suggestNextStableVersion(string $currentVersion): string
    {
        $components = $this->parseVersion($currentVersion);

        // Suggest next patch version
        return "{$components['major']}.{$components['minor']}.".($components['patch'] + 1);
    }

    /**
     * Get version type
     */
    public function getVersionType(string $version): string
    {
        if ($this->isPrerelease($version)) {
            $components = $this->parseVersion($version);

            return $components['prerelease'];
        }

        return 'stable';
    }

    /**
     * Format version for display
     */
    public function formatVersionForDisplay(string $version): string
    {
        if ($this->isPrerelease($version)) {
            $components = $this->parseVersion($version);
            $type = match ($components['prerelease']) {
                'alpha' => 'Alpha',
                'beta' => 'Beta',
                'rc' => 'Release Candidate',
                default => ucfirst($components['prerelease'])
            };

            return "v{$version} ({$type})";
        }

        return "v{$version}";
    }

    /**
     * Create version suggestions based on type
     */
    public function createVersionSuggestions(string $currentVersion): array
    {
        $components = $this->parseVersion($currentVersion);
        $suggestions = [];

        // Patch version
        $patchVersion = "{$components['major']}.{$components['minor']}.".($components['patch'] + 1);
        $suggestions['patch'] = $patchVersion;

        // Minor version
        $minorVersion = "{$components['major']}.".($components['minor'] + 1).'.0';
        $suggestions['minor'] = $minorVersion;

        // Major version
        $majorVersion = ($components['major'] + 1).'.0.0';
        $suggestions['major'] = $majorVersion;

        // Prerelease versions
        if (! $this->isPrerelease($currentVersion)) {
            $suggestions['alpha'] = "{$patchVersion}-alpha.1";
            $suggestions['beta'] = "{$patchVersion}-beta.1";
            $suggestions['rc'] = "{$patchVersion}-rc.1";
        }

        return $suggestions;
    }
}
