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

        // Log the error response
        $errorMessage = $response->body();
        $statusCode = $response->status();
        \Log::error("GitHub API GET ({$url}) request failed: Status {$statusCode}, Message: {$errorMessage}");

        return null;
    }

    public function postJson(string $url, array $data = []): ?array
    {
        $response = Http::withHeaders($this->headers)->post($url, $data);

        if ($response->successful()) {
            return $response->json();
        }

        // Log the error response
        $errorMessage = $response->body();
        $statusCode = $response->status();
        \Log::error("GitHub API POST ({$url}) request failed: Status {$statusCode}, Message: {$errorMessage}");

        return null;
    }

    public function getOrgHostedRunners(string $org): ?array
    {
        $url = "https://api.github.com/orgs/{$org}/actions/hosted-runners";

        return $this->fetchJson($url);
    }

    public function getWorkflows(string $org, string $repo): ?array
    {
        $url = "https://api.github.com/repos/{$org}/{$repo}/actions/workflows";

        return $this->fetchJson($url);
    }

    public function getCurrentUser(): ?array
    {
        $url = 'https://api.github.com/user';

        return $this->fetchJson($url);
    }

    public function triggerWorkflowDispatch(string $org, string $repo, string $workflowId, string $ref = 'main', array $inputs = []): ?array
    {
        $url = "https://api.github.com/repos/{$org}/{$repo}/actions/workflows/{$workflowId}/dispatches";

        // Automatically add user information to inputs
        $user = $this->getCurrentUser();
        if ($user && ! isset($inputs['user_name']) && ! isset($inputs['user_email'])) {
            $inputs['user_name'] = $user['name'] ?? $user['login'] ?? 'Unknown User';
            $inputs['user_email'] = $user['email'] ?? $user['login'].'@users.noreply.github.com';
        }

        $data = [
            'ref' => $ref,
        ];

        if (! empty($inputs)) {
            $data['inputs'] = $inputs;
        }

        return $this->postJson($url, $data);
    }

    public function createRelease(string $repoFullName, string $version, ?string $body = null, string $targetCommitish = 'main'): ?array
    {
        $url = "https://api.github.com/repos/{$repoFullName}/releases";

        // Detect if this is a prerelease (alpha, beta, rc)
        $isPrerelease = preg_match('/-(alpha|beta|rc)\b/i', $version);

        $data = [
            'tag_name' => "v{$version}",
            'target_commitish' => $targetCommitish,
            'name' => $version,
            'body' => $body ?? "Release version {$version}",
            'draft' => false,
            'prerelease' => $isPrerelease,
            'generate_release_notes' => false,
        ];

        return $this->postJson($url, $data);
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
                        'minimum-stability' => $composer['extra']['moox']['stability'] ?? 'dev',
                        'visibility' => $visibility,
                    ];
                }
            }
        }

        return $packages;
    }
}
