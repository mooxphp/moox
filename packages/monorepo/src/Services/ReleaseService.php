<?php

namespace Moox\Monorepo\Services;

class ReleaseService
{
    public function __construct(
        protected GitHubService $github,
        protected string $mainRepo,
        protected string $org
    ) {}

    public function getVersionsOverview(): array
    {
        $repoInfo = $this->github->getRepoInfo($this->mainRepo);

        if (! $repoInfo) {
            throw new \RuntimeException("Main repo '{$this->mainRepo}' not accessible.");
        }

        $repos = $this->github->getOrgRepositories($this->org);

        $counts = [
            'total' => 0,
            'private' => 0,
            'public' => 0,
            'with_release' => 0,
            'without_release' => 0,
        ];

        $rows = $repos->map(function ($repo) use (&$counts) {
            $counts['total']++;
            $repo['private'] ? $counts['private']++ : $counts['public']++;

            $releaseTag = $this->github->getLatestReleaseTag($repo['full_name']);
            if ($releaseTag !== 'No tag') {
                $counts['with_release']++;
            } else {
                $counts['without_release']++;
            }

            return [
                $repo['name'],
                $repo['description'] ?? '–',
                $repo['full_name'],
                $repo['visibility'] ?? '–',
                $releaseTag,
            ];
        });

        $mainVersion = $this->github->getLatestReleaseTag($this->mainRepo);
        $mainVersion = ltrim($mainVersion, 'v');

        return [
            'version' => $mainVersion,
            'repos' => $rows,
            'stats' => $counts,
        ];
    }
}
