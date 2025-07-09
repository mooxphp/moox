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

    public function isNewOrgPackage(array $publicPackages, array $privatePackages, array $orgRepositories): ?array
    {
        $allExistingPackages = array_merge($publicPackages, $privatePackages);
        
        $newPackages = array_filter(array_keys($allExistingPackages), function($package) use ($orgRepositories) {
            return !in_array($package, $orgRepositories);
        });

        return !empty($newPackages) ? array_values($newPackages) : null;

    }
}
