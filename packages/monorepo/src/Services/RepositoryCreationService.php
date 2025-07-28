<?php

namespace Moox\Monorepo\Services;

use Illuminate\Support\Collection;
use Moox\Monorepo\Contracts\GitHubClientInterface;
use Moox\Monorepo\DataTransferObjects\PackageInfo;

class RepositoryCreationService
{
    public function __construct(
        private GitHubClientInterface $githubClient,
        private DevlinkService $devlinkService
    ) {
    }

    /**
     * Find missing repositories for packages
     */
    public function findMissingRepositories(string $type): Collection
    {
        $organization = config('monorepo.github.organization');
        $repoName = $type === 'public' 
            ? config('monorepo.github.public_repo')
            : config('monorepo.github.private_repo');

        if (!$repoName) {
            return collect();
        }

        // Get packages from GitHub monorepo
        $packages = $this->githubClient->getMonorepoPackages($organization, $repoName, 'packages');
        
        // Convert to PackageInfo objects
        $packageInfos = $packages->map(function ($package) use ($type) {
            return new PackageInfo(
                name: $package['name'],
                path: '',
                visibility: $type,
                stability: $package['stability'] ?? 'dev',
                description: $package['composer']['description'] ?? null,
                composer: $package['composer'] ?? []
            );
        });

        // Compare with organization repositories
        $orgRepositories = $this->githubClient->getOrganizationRepositories($organization);
        $repoNames = $orgRepositories->pluck('name')->toArray();
        
        // Return only packages that don't have corresponding repositories
        return $packageInfos->filter(function ($package) use ($repoNames) {
            return !in_array($package->name, $repoNames);
        });
    }

    /**
     * Create repositories for missing packages
     */
    public function createRepositories(Collection $missingPackages, bool $updateDevlink = true): array
    {
        $organization = config('monorepo.github.organization');
        $created = 0;
        $failed = 0;
        $devlinkUpdated = 0;
        $errors = [];

        foreach ($missingPackages as $package) {
            try {
                $this->createSingleRepository($package, $organization);
                $created++;
                
                // Update devlink configuration
                if ($updateDevlink) {
                    try {
                        $this->updateDevlinkConfig($package);
                        $devlinkUpdated++;
                    } catch (\Exception $e) {
                        $errors[] = "Failed to update devlink config for {$package->name}: {$e->getMessage()}";
                    }
                }
                
                // Small delay to avoid rate limiting
                usleep(100000); // 0.1 seconds
                
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Failed to create repository for {$package->name}: {$e->getMessage()}";
            }
        }

        return [
            'created' => $created,
            'failed' => $failed,
            'devlink_updated' => $devlinkUpdated,
            'errors' => $errors,
        ];
    }

    /**
     * Create a single repository
     */
    private function createSingleRepository(PackageInfo $package, string $organization): void
    {
        $isPrivate = $package->visibility === 'private';
        
        // Use configurable repository settings
        $options = [
            'description' => $package->description ?: "Package repository for {$package->name}",
            'private' => $isPrivate,
            'visibility' => $isPrivate ? 'private' : 'public',
            // Repository features - configurable with repository defaults
            'has_issues' => config('monorepo.repository.has_issues', true),
            'has_projects' => config('monorepo.repository.has_projects', false),
            'has_wiki' => config('monorepo.repository.has_wiki', false),
            'has_pages' => false, // Not configurable as GitHub requires special setup
            'has_discussions' => config('monorepo.repository.has_discussions', false),
            'allow_forking' => config('monorepo.repository.allow_forking', true),
            'web_commit_signoff_required' => config('monorepo.repository.web_commit_signoff_required', false),
            // Repository initialization - keep empty for workflow to populate
            'auto_init' => config('monorepo.repository.auto_init', false),
            'gitignore_template' => config('monorepo.repository.gitignore_template', null),
            'license_template' => config('monorepo.repository.default_license', null),
            // Merge settings - configurable repository preferences
            'allow_squash_merge' => config('monorepo.repository.allow_squash_merge', true),
            'allow_merge_commit' => config('monorepo.repository.allow_merge_commit', false),
            'allow_rebase_merge' => config('monorepo.repository.allow_rebase_merge', false),
            'allow_auto_merge' => config('monorepo.repository.allow_auto_merge', false),
            'delete_branch_on_merge' => config('monorepo.repository.delete_branch_on_merge', true),
        ];

        $result = $this->githubClient->createRepository($organization, $package->name, $options);

        if (!$result) {
            throw new \Exception('Repository creation failed - no response from GitHub API');
        }

        // Store the URL for later reference
        $url = $result['html_url'] ?? "https://github.com/{$organization}/{$package->name}";
        $package->repositoryUrl = $url;
    }

    /**
     * Update the devlink configuration with a new package
     */
    private function updateDevlinkConfig(PackageInfo $package): void
    {
        // Check if package already exists in devlink config
        if ($this->devlinkService->packageExistsInDevlink($package->name)) {
            return; // Already exists, no need to update
        }

        // Add the package to devlink config
        $this->devlinkService->addPackagesToDevlinkConfig(collect([$package]));
    }

    /**
     * Validate GitHub access
     */
    public function validateGitHubAccess(): bool
    {
        try {
            $user = $this->githubClient->getCurrentUser();
            return $user !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
} 