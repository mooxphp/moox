<?php
namespace Moox\Monorepo\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class GitHubService
{
    protected array $headers;

    public function __construct(string $token)
    {
        if (! $token) {
            throw new \InvalidArgumentException('Missing GitHub token.');
        }

        $this->headers = [
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer ' . $token,
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }

    public function fetchJson(string $url): array|null
    {
        $response = Http::withHeaders($this->headers)->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function getLatestReleaseTag(string $repoFullName): string
    {
        $data = $this->fetchJson("https://api.github.com/repos/{$repoFullName}/releases/latest");

        return $data['name'] ?? 'No release';
    }

    public function getOrgRepositories(string $org): Collection
    {
        $data = $this->fetchJson("https://api.github.com/orgs/{$org}/repos?type=all&per_page=100");

        return collect($data ?? []);
    }

    public function getRepoInfo(string $repo): array|null
    {
        return $this->fetchJson("https://api.github.com/repos/{$repo}");

    }

    
}
