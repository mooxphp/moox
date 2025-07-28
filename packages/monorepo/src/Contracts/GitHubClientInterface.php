<?php

namespace Moox\Monorepo\Contracts;

use Illuminate\Support\Collection;

interface GitHubClientInterface
{
    /**
     * Get current GitHub user info
     */
    public function getCurrentUser(): ?array;

    /**
     * Get organization repositories
     */
    public function getOrganizationRepositories(string $organization): Collection;

    /**
     * Get repository information
     */
    public function getRepository(string $organization, string $repository): ?array;

    /**
     * Get latest release tag for a repository
     */
    public function getLatestReleaseTag(string $organization, string $repository): ?string;

    /**
     * Create a new release
     */
    public function createRelease(
        string $organization,
        string $repository,
        string $version,
        ?string $body = null,
        bool $isPrerelease = false
    ): ?array;

    /**
     * Trigger a workflow dispatch
     */
    public function triggerWorkflow(
        string $organization,
        string $repository,
        string $workflowFile,
        array $inputs = []
    ): bool;

    /**
     * Get packages from a monorepo
     */
    public function getMonorepoPackages(
        string $organization,
        string $repository,
        string $path
    ): Collection;

    /**
     * Create a new repository in the organization
     */
    public function createRepository(
        string $organization,
        string $name,
        array $options = []
    ): ?array;
} 