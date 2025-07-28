<?php

namespace Moox\Monorepo\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Moox\Monorepo\Contracts\ChangelogProcessorInterface;
use Moox\Monorepo\DataTransferObjects\PackageChange;

class ProcessChangelogAction implements ChangelogProcessorInterface
{
    private Collection $parsedChanges;

    public function __construct(
        private string $changelogPath
    ) {
        $this->parsedChanges = collect();
    }

    /**
     * Parse changelog file and extract changes by package
     */
    public function parseChangelog(string $changelogPath): Collection
    {
        if (!File::exists($changelogPath)) {
            return collect();
        }

        $content = File::get($changelogPath);
        $lines = explode("\n", $content);

        $changes = collect();
        $currentPackage = null;

        foreach ($lines as $line) {
            // Match package headers like "## PackageName"
            if (preg_match('/^##\s+(.*)$/', $line, $matches)) {
                $currentPackage = trim($matches[1]);
                if (!$changes->has(strtolower($currentPackage))) {
                    $changes->put(strtolower($currentPackage), collect());
                }
            } 
            // Match change entries like "- Some change"
            elseif ($currentPackage && preg_match('/^-\s+(.*)$/', $line, $matches)) {
                $change = trim($matches[1]);
                if (!empty($change)) {
                    $changes->get(strtolower($currentPackage))->push($change);
                }
            }
        }

        $this->parsedChanges = $changes;
        return $changes;
    }

    /**
     * Get changes for a specific package
     */
    public function getPackageChanges(string $packageName): Collection
    {
        if ($this->parsedChanges->isEmpty()) {
            $this->parseChangelog($this->changelogPath);
        }

        return $this->parsedChanges->get(strtolower($packageName), collect());
    }

    /**
     * Generate release message for a package
     */
    public function generateReleaseMessage(string $packageName, ?string $stability = null): string
    {
        $changes = $this->getPackageChanges($packageName);

        if ($changes->isEmpty()) {
            return match ($stability) {
                'init' => 'Initial release',
                default => 'Compatibility release'
            };
        }

        return $changes->count() === 1 
            ? $changes->first() 
            : $changes->implode('; ');
    }

    /**
     * Check if package has explicit changes
     */
    public function hasExplicitChanges(string $packageName): bool
    {
        return $this->getPackageChanges($packageName)->isNotEmpty();
    }

    /**
     * Get all packages with their changes
     */
    public function getAllPackagesWithChanges(string $packageType = 'public'): Collection
    {
        if ($this->parsedChanges->isEmpty()) {
            $this->parseChangelog($this->changelogPath);
        }

        return $this->parsedChanges->map(function (Collection $changes, string $packageName) use ($packageType) {
            if ($changes->isEmpty()) {
                return PackageChange::compatibility($packageName, $packageType);
            }

            return PackageChange::withChanges($packageName, $changes->toArray(), $packageType);
        });
    }

    /**
     * Create package change for a package with known stability
     */
    public function createPackageChange(string $packageName, string $stability, string $packageType = 'public'): PackageChange
    {
        $changes = $this->getPackageChanges($packageName);

        if ($changes->isNotEmpty()) {
            return PackageChange::withChanges($packageName, $changes->toArray(), $packageType);
        }

        return match ($stability) {
            'init' => PackageChange::initial($packageName, $packageType),
            default => PackageChange::compatibility($packageName, $packageType)
        };
    }
} 