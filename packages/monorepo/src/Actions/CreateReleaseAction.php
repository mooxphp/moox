<?php

namespace Moox\Monorepo\Actions;

use Illuminate\Support\Collection;
use Moox\Monorepo\Contracts\GitHubClientInterface;
use Moox\Monorepo\Contracts\VersionManagerInterface;
use Moox\Monorepo\DataTransferObjects\ReleaseInfo;
use Moox\Monorepo\DataTransferObjects\PackageChange;

class CreateReleaseAction
{
    public function __construct(
        private GitHubClientInterface $githubClient,
        private VersionManagerInterface $versionManager
    ) {}

    /**
     * Create a release for a repository
     */
    public function createRelease(ReleaseInfo $releaseInfo): bool
    {
        $result = $this->githubClient->createRelease(
            $releaseInfo->organization,
            $releaseInfo->repository,
            $releaseInfo->version,
            $releaseInfo->body,
            $releaseInfo->isPrerelease
        );

        return $result !== null;
    }

    /**
     * Prepare packages for workflow dispatch
     */
    public function preparePackagesForWorkflow(Collection $packageChanges): array
    {
        $maxSize = config('monorepo.release.max_payload_size', 50000);
        
        // Convert package changes to workflow format
        $packages = $packageChanges->mapWithKeys(function (PackageChange $change) {
            return [
                $change->packageName => [
                    'release-message' => [$change->getSanitizedReleaseMessage()],
                    'change-type' => $change->changeType,
                    'moox-stability' => $change->metadata['moox_stability'] ?? 'dev',
                ]
            ];
        })->toArray();

        $json = json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // If payload is too large, truncate messages
        if (strlen($json) > $maxSize) {
            $packages = $this->truncatePackagesPayload($packages, $maxSize);
            $json = json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return $packages;
    }

    /**
     * Trigger workflow for packages
     */
    public function triggerWorkflow(
        string $organization,
        string $repository,
        string $version,
        array $packages,
        ?string $userToken = null
    ): bool {
        $inputs = [
            'version' => $version,
            'packages' => json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];

        if ($userToken) {
            $inputs['user_token'] = $userToken;
        }

        return $this->githubClient->triggerWorkflow(
            $organization,
            $repository,
            config('monorepo.release.workflow_file', 'split.yml'),
            $inputs
        );
    }

    /**
     * Create releases for multiple repositories
     */
    public function createMultipleReleases(Collection $releaseInfos): Collection
    {
        return $releaseInfos->map(function (ReleaseInfo $releaseInfo) {
            $success = $this->createRelease($releaseInfo);
            
            return $releaseInfo->withMetadata([
                'created' => $success,
                'created_at' => now()->toISOString(),
            ]);
        });
    }

    /**
     * Process a complete release with packages
     */
    public function processCompleteRelease(
        ReleaseInfo $releaseInfo,
        Collection $packageChanges,
        ?string $userToken = null
    ): array {
        // Create the release
        $releaseCreated = $this->createRelease($releaseInfo);
        
        if (!$releaseCreated) {
            return [
                'success' => false,
                'error' => 'Failed to create release',
                'release' => $releaseInfo->toArray(),
            ];
        }

        // Prepare and trigger workflow
        $packages = $this->preparePackagesForWorkflow($packageChanges);
        $workflowTriggered = $this->triggerWorkflow(
            $releaseInfo->organization,
            $releaseInfo->repository,
            $releaseInfo->version,
            $packages,
            $userToken
        );

        return [
            'success' => true,
            'release' => $releaseInfo->toArray(),
            'workflow_triggered' => $workflowTriggered,
            'packages_count' => $packageChanges->count(),
            'packages' => $packages,
        ];
    }

    /**
     * Truncate packages payload to fit size limits
     */
    private function truncatePackagesPayload(array $packages, int $maxSize): array
    {
        $truncated = [];
        
        foreach ($packages as $name => $data) {
            $truncated[$name] = [
                'release-message' => ['Release update'],
                'change-type' => $data['change-type'] ?? 'compatibility',
                'moox-stability' => $data['moox-stability'] ?? 'dev',
            ];
        }

        return $truncated;
    }
} 