<?php

namespace Moox\Monorepo\DataTransferObjects;

use Illuminate\Support\Collection;

class ReleaseInfo
{
    public function __construct(
        public readonly string $version,
        public readonly string $organization,
        public readonly string $repository,
        public readonly bool $isPrerelease = false,
        public readonly ?string $body = null,
        public readonly Collection $packages = new Collection,
        public readonly array $metadata = []
    ) {}

    /**
     * Create from version string
     */
    public static function create(
        string $version,
        string $organization,
        string $repository,
        ?string $body = null
    ): self {
        $isPrerelease = preg_match('/-(alpha|beta|rc)\b/i', $version);

        return new self(
            version: $version,
            organization: $organization,
            repository: $repository,
            isPrerelease: (bool) $isPrerelease,
            body: $body
        );
    }

    /**
     * Add packages to the release
     */
    public function withPackages(Collection $packages): self
    {
        return new self(
            version: $this->version,
            organization: $this->organization,
            repository: $this->repository,
            isPrerelease: $this->isPrerelease,
            body: $this->body,
            packages: $packages,
            metadata: $this->metadata
        );
    }

    /**
     * Add metadata to the release
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            version: $this->version,
            organization: $this->organization,
            repository: $this->repository,
            isPrerelease: $this->isPrerelease,
            body: $this->body,
            packages: $this->packages,
            metadata: array_merge($this->metadata, $metadata)
        );
    }

    /**
     * Get the full tag name
     */
    public function getTagName(): string
    {
        return "v{$this->version}";
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'organization' => $this->organization,
            'repository' => $this->repository,
            'isPrerelease' => $this->isPrerelease,
            'body' => $this->body,
            'packages' => $this->packages->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}
