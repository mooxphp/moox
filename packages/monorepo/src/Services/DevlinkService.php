<?php

namespace Moox\Monorepo\Services;

use Illuminate\Support\Collection;

class DevlinkService
{
    private string $devlinkConfigPath;

    public function __construct()
    {
        $this->devlinkConfigPath = config_path('devlink.php');
    }

    /**
     * Add new packages to the devlink configuration
     */
    public function addPackagesToDevlinkConfig(Collection $packages): array
    {
        if (! file_exists($this->devlinkConfigPath)) {
            throw new \RuntimeException("Devlink config file not found: {$this->devlinkConfigPath}");
        }

        $originalContent = file_get_contents($this->devlinkConfigPath);
        $updatedContent = $originalContent;
        $addedPackages = [];

        foreach ($packages as $package) {
            $packageKey = strtolower($package->name);

            // Check if package already exists in devlink config
            if (strpos($updatedContent, "'{$packageKey}'") !== false) {
                continue; // Skip if already exists
            }

            $newPackageEntry = $this->generatePackageEntry($packageKey, $package->visibility);

            // Find the right alphabetical position and insert
            $updatedContent = $this->insertPackageAlphabetically($updatedContent, $packageKey, $newPackageEntry);

            $addedPackages[] = $packageKey;
        }

        // Write the updated content back to the file
        if (file_put_contents($this->devlinkConfigPath, $updatedContent) === false) {
            throw new \RuntimeException("Failed to write to devlink config file: {$this->devlinkConfigPath}");
        }

        return $addedPackages;
    }

    /**
     * Generate a package entry for the devlink configuration
     */
    private function generatePackageEntry(string $packageKey, string $visibility): string
    {
        $publicBasePath = config('devlink.public_base_path', '../moox/packages');
        $privateBasePath = config('devlink.private_base_path', 'disabled');

        $basePath = $visibility === 'private' ? $privateBasePath : $publicBasePath;
        $type = $visibility === 'private' ? 'private' : 'public';

        $entry = "        '{$packageKey}' => [\n";
        $entry .= "            'active' => false,\n";
        $entry .= "            'path' => \$public_base_path.'/{$packageKey}',\n";
        $entry .= "            'type' => '{$type}',\n";

        if ($visibility === 'private') {
            $privateRepoUrl = config('devlink.private_repo_url', 'https://pkg.moox.pro/');
            $entry .= "            'repo_url' => \$private_repo_url,\n";
        }

        $entry .= "        ],\n";

        return $entry;
    }

    /**
     * Insert a package entry in alphabetical order
     */
    private function insertPackageAlphabetically(string $content, string $packageKey, string $newPackageEntry): string
    {
        // Find all existing package entries
        preg_match_all("/^        '([^']+)'/m", $content, $matches);
        $existingPackages = $matches[1];

        // Find the correct insertion position
        $insertAfter = null;
        foreach ($existingPackages as $existingPackage) {
            if (strcmp($packageKey, $existingPackage) > 0) {
                $insertAfter = $existingPackage;
            } else {
                break;
            }
        }

        if ($insertAfter) {
            // Insert after the found package
            $pattern = "/^(        '{$insertAfter}' => \[.*?\],)\n/ms";
            $content = preg_replace($pattern, "$1\n{$newPackageEntry}", $content, 1, $count);

            if ($count === 0) {
                // Fallback: insert at the beginning of packages array
                $content = preg_replace("/(    'packages' => \[\n)/", "$1{$newPackageEntry}", $content, 1);
            }
        } else {
            // Insert at the beginning of packages array
            $content = preg_replace("/(    'packages' => \[\n)/", "$1{$newPackageEntry}", $content, 1);
        }

        return $content;
    }

    /**
     * Check if a package already exists in the devlink configuration
     */
    public function packageExistsInDevlink(string $packageName): bool
    {
        if (! file_exists($this->devlinkConfigPath)) {
            return false;
        }

        $content = file_get_contents($this->devlinkConfigPath);
        $packageKey = strtolower($packageName);

        return strpos($content, "'{$packageKey}'") !== false;
    }

    /**
     * Get the path to the devlink configuration file
     */
    public function getConfigPath(): string
    {
        return $this->devlinkConfigPath;
    }
}
