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
        return $comparison->filter(fn($exists) => !$exists);
    }

    public function extractDevlinkPackages(): array
    {
        $devlinkConfig = config('devlink.packages');
        return array_keys($devlinkConfig);
    }

    public function isNewPackage(string $package): bool
    {
        $publicBasePath = config('devlink.public_base_path', '../moox/packages');
        $privateBasePath = config('devlink.private_base_path', 'disabled');
        
        $localPackages = collect(array_merge(
            \Illuminate\Support\Facades\File::directories(base_path($publicBasePath)),
            $privateBasePath !== 'disabled' ? \Illuminate\Support\Facades\File::directories(base_path($privateBasePath)) : []
        ))->map(fn($dir) => basename($dir))
          ->toArray();
          
        return !in_array(strtolower($package), array_map('strtolower', $localPackages));
            
    }


} 