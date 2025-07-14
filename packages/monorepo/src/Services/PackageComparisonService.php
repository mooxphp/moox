<?php

namespace Moox\Monorepo\Services;

use Illuminate\Support\Collection;

class PackageComparisonService
{
    protected GitHubService $githubService;

    protected string $organization;

    public function __construct(GitHubService $githubService, string $organization)
    {
        $this->githubService = $githubService;
        $this->organization = $organization;
    }

    public function comparePackagesWithRepositories(array $localPackages, array $devlinkPackages): Collection
    {
        $allPackages = array_merge($localPackages, $devlinkPackages);
        $repositories = $this->githubService->getOrgRepositories($this->organization);
        $repoNames = $repositories->pluck('name')->toArray();

        $comparison = collect($allPackages)->mapWithKeys(function ($package) use ($repoNames) {
            return [$package => in_array($package, $repoNames)];
        });

        return $comparison;
    }

    public function extractNotPublishedPackages(Collection $comparison): Collection
    {
        return $comparison->filter(fn ($exists) => ! $exists);
    }

    public function extractDevlinkPackages(): array
    {
        $devlinkConfig = config('devlink.packages');

        return array_keys($devlinkConfig);
    }

    public function isNewOrgPackage(array $publicPackages, array $privatePackages, array $orgRepositories): array
    {
        $missingPackages = [];

        // Check public packages
        foreach ($publicPackages as $package) {
            if (!in_array($package, $orgRepositories)) {
                $missingPackages['public'][] = $package;
            }
        }

        // Check private packages
        foreach ($privatePackages as $package) {
            if (!in_array($package, $orgRepositories)) {
                $missingPackages['private'][] = $package;
            }
        }

        return $missingPackages;
    }
}
