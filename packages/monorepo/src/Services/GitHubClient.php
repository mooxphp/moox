<?php

namespace Moox\Monorepo\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Moox\Monorepo\Contracts\GitHubClientInterface;

class GitHubClient implements GitHubClientInterface
{
    private array $headers;

    private bool $cacheEnabled;

    private int $cacheTtl;

    private string $cachePrefix;

    public function __construct(?string $token = null)
    {
        $this->headers = [
            'Accept' => 'application/vnd.github+json',
            'Authorization' => "Bearer {$token}",
            'X-GitHub-Api-Version' => config('monorepo.github.api_version', '2022-11-28'),
        ];

        if ($token) {
            $this->headers['Authorization'] = "Bearer {$token}";
        }

        $this->cacheEnabled = config('monorepo.cache.enabled', true);
        $this->cacheTtl = config('monorepo.cache.ttl', 300);
        $this->cachePrefix = config('monorepo.cache.prefix', 'monorepo_v2');
    }

    /**
     * Ensure GitHub token is available before making API calls
     */
    private function ensureToken(): void
    {
        if (! isset($this->headers['Authorization'])) {
            throw new \RuntimeException('GitHub token not found. Please link your GitHub account.');
        }
    }

    /**
     * Get current GitHub user info
     */
    public function getCurrentUser(): ?array
    {
        return $this->cachedRequest('user', fn () => $this->get('https://api.github.com/user'));
    }

    /**
     * Get organization repositories
     */
    public function getOrganizationRepositories(string $organization): Collection
    {
        $cacheKey = "org_repos_{$organization}";

        return $this->cachedRequest($cacheKey, function () use ($organization) {
            $repos = collect();
            $page = 1;
            $perPage = 100;

            do {
                $url = "https://api.github.com/orgs/{$organization}/repos";
                $response = $this->get($url, [
                    'type' => 'all',
                    'per_page' => $perPage,
                    'page' => $page,
                ]);

                if (! $response) {
                    break;
                }

                $pageRepos = collect($response);
                $repos = $repos->concat($pageRepos);
                $page++;
            } while ($pageRepos->count() === $perPage);

            return $repos;
        });
    }

    /**
     * Get repository information
     */
    public function getRepository(string $organization, string $repository): ?array
    {
        $cacheKey = "repo_{$organization}_{$repository}";

        return $this->cachedRequest($cacheKey, function () use ($organization, $repository) {
            return $this->get("https://api.github.com/repos/{$organization}/{$repository}");
        });
    }

    /**
     * Get latest release tag for a repository
     */
    public function getLatestReleaseTag(string $organization, string $repository): ?string
    {
        $cacheKey = "latest_release_{$organization}_{$repository}";

        return $this->cachedRequest($cacheKey, function () use ($organization, $repository) {
            $response = $this->get("https://api.github.com/repos/{$organization}/{$repository}/releases/latest");

            return $response['name'] ?? $response['tag_name'] ?? null;
        }, 60); // Shorter cache for release tags
    }

    /**
     * Create a new release
     */
    public function createRelease(
        string $organization,
        string $repository,
        string $version,
        ?string $body = null,
        bool $isPrerelease = false
    ): ?array {
        $data = [
            'tag_name' => "v{$version}",
            'name' => $version,
            'body' => $body ?? "Release version {$version}",
            'draft' => false,
            'prerelease' => $isPrerelease,
            'generate_release_notes' => config('monorepo.release.auto_generate_notes', false),
        ];

        $response = $this->post("https://api.github.com/repos/{$organization}/{$repository}/releases", $data);

        if ($response) {
            // Clear cache for this repository's releases
            $this->clearCachePattern("latest_release_{$organization}_{$repository}");
        }

        return $response;
    }

    /**
     * Trigger a workflow dispatch
     */
    public function triggerWorkflow(
        string $organization,
        string $repository,
        string $workflowFile,
        array $inputs = []
    ): bool {
        $data = [
            'ref' => config('monorepo.github.default_branch', 'main'),
        ];

        if (! empty($inputs)) {
            $data['inputs'] = $inputs;
        }

        $url = "https://api.github.com/repos/{$organization}/{$repository}/actions/workflows/{$workflowFile}/dispatches";
        $response = $this->post($url, $data);

        return $response !== null;
    }

    /**
     * Get packages from a monorepo
     */
    public function getMonorepoPackages(
        string $organization,
        string $repository,
        string $path
    ): Collection {
        $cacheKey = "monorepo_packages_{$organization}_{$repository}_{$path}";

        return $this->cachedRequest($cacheKey, function () use ($organization, $repository, $path) {
            $contents = $this->get("https://api.github.com/repos/{$organization}/{$repository}/contents/{$path}");

            if (! $contents) {
                return collect();
            }

            return collect($contents)
                ->filter(fn ($item) => $item['type'] === 'dir')
                ->map(function ($item) use ($organization, $repository, $path) {
                    $composerUrl = "https://api.github.com/repos/{$organization}/{$repository}/contents/{$path}/{$item['name']}/composer.json";
                    $composerData = $this->get($composerUrl);

                    if (! $composerData) {
                        return null;
                    }

                    $composer = json_decode(base64_decode($composerData['content']), true);

                    return [
                        'name' => $item['name'],
                        'composer' => $composer,
                        'stability' => $composer['extra']['moox-stability'] ?? $composer['extra']['moox']['stability'] ?? 'dev',
                    ];
                })
                ->filter();
        });
    }

    /**
     * Create a new repository in the organization
     */
    public function createRepository(
        string $organization,
        string $name,
        array $options = []
    ): ?array {
        $data = array_merge([
            'name' => $name,
            'description' => $options['description'] ?? "Package repository for {$name}",
            'private' => $options['private'] ?? false,
            'visibility' => $options['visibility'] ?? ($options['private'] ?? false ? 'private' : 'public'),
            'has_issues' => $options['has_issues'] ?? true,
            'has_projects' => $options['has_projects'] ?? false,
            'has_wiki' => $options['has_wiki'] ?? false,
            'has_discussions' => $options['has_discussions'] ?? false,
            'auto_init' => $options['auto_init'] ?? false, // Don't auto-init since workflow will handle it
            'gitignore_template' => $options['gitignore_template'] ?? null,
            'license_template' => $options['license_template'] ?? null,
            'allow_squash_merge' => $options['allow_squash_merge'] ?? true,
            'allow_merge_commit' => $options['allow_merge_commit'] ?? false,
            'allow_rebase_merge' => $options['allow_rebase_merge'] ?? false,
            'allow_auto_merge' => $options['allow_auto_merge'] ?? false,
            'delete_branch_on_merge' => $options['delete_branch_on_merge'] ?? true,
        ], $options);

        $response = $this->post("https://api.github.com/orgs/{$organization}/repos", $data);

        if ($response) {
            // Clear cache for organization repositories
            $this->clearCachePattern("org_repos_{$organization}");
        }

        return $response;
    }

    /**
     * Make HTTP GET request
     */
    private function get(string $url, array $query = []): ?array
    {
        $this->ensureToken();

        try {
            $response = Http::withHeaders($this->headers)->get($url, $query);

            return $this->handleResponse($response, 'GET', $url);
        } catch (\Exception $e) {
            Log::error('GitHub API GET request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Make HTTP POST request
     */
    private function post(string $url, array $data = []): ?array
    {
        $this->ensureToken();

        try {
            $response = Http::withHeaders($this->headers)->post($url, $data);

            return $this->handleResponse($response, 'POST', $url);
        } catch (\Exception $e) {
            Log::error('GitHub API POST request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Handle HTTP response
     */
    private function handleResponse(Response $response, string $method, string $url): ?array
    {
        if ($response->successful()) {
            return $response->json();
        }

        Log::error("GitHub API {$method} request failed", [
            'url' => $url,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Cache a request result
     */
    private function cachedRequest(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if (! $this->cacheEnabled) {
            return $callback();
        }

        $cacheKey = "{$this->cachePrefix}:{$key}";
        $ttl = $ttl ?? $this->cacheTtl;

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Clear cache entries matching a pattern
     */
    private function clearCachePattern(string $pattern): void
    {
        if (! $this->cacheEnabled) {
            return;
        }

        Cache::forget("{$this->cachePrefix}:{$pattern}");
    }
}
