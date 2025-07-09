<?php

namespace Moox\Monorepo\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

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
            'Authorization' => 'Bearer '.$token,
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }

    public function fetchJson(string $url): ?array
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

    public function getRepoInfo(string $repo): ?array
    {
        return $this->fetchJson("https://api.github.com/repos/{$repo}");
    }

    public function getMonorepoPackages($owner, $repo, $path, $visibility = 'public'): array
    {
        $data = $this->fetchJson("https://api.github.com/repos/{$owner}/{$repo}/contents/{$path}");
        $packages = [];
        
        foreach ($data ?? [] as $item) {
            if ($item['type'] === 'dir') {
                $composerJson = $this->fetchJson("https://api.github.com/repos/{$owner}/{$repo}/contents/{$path}/{$item['name']}/composer.json");
                if ($composerJson) {
                    $content = base64_decode($composerJson['content']);
                    $composer = json_decode($content, true);
                    $packages[$item['name']] = [
                        'minimum-stability' => $composer['minimum-stability'] ?? 'stable',
                        'visibility' => $visibility,
                    ];
                }
            }
        }

        return $packages;
    }
}
