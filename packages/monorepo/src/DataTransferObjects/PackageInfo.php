<?php

namespace Moox\Monorepo\DataTransferObjects;

class PackageInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly string $visibility = 'public',
        public readonly string $stability = 'dev',
        public readonly ?string $description = null,
        public readonly array $composer = [],
        public readonly bool $existsInOrganization = false
    ) {}

    /**
     * Get type (alias for visibility for backward compatibility)
     */
    public function __get(string $name): mixed
    {
        if ($name === 'type') {
            return $this->visibility;
        }

        throw new \InvalidArgumentException("Property {$name} does not exist");
    }

    /**
     * Create from composer.json data
     */
    public static function fromComposer(string $name, string $path, array $composer, string $visibility = 'public'): self
    {
        $stability = $composer['extra']['moox']['stability'] ?? 'dev';
        $description = $composer['description'] ?? null;

        return new self(
            name: $name,
            path: $path,
            visibility: $visibility,
            stability: $stability,
            description: $description,
            composer: $composer
        );
    }

    /**
     * Create a copy with updated properties
     */
    public function with(array $properties): self
    {
        // Handle 'type' as alias for 'visibility'
        if (isset($properties['type'])) {
            $properties['visibility'] = $properties['type'];
            unset($properties['type']);
        }

        return new self(
            name: $properties['name'] ?? $this->name,
            path: $properties['path'] ?? $this->path,
            visibility: $properties['visibility'] ?? $this->visibility,
            stability: $properties['stability'] ?? $this->stability,
            description: $properties['description'] ?? $this->description,
            composer: $properties['composer'] ?? $this->composer,
            existsInOrganization: $properties['existsInOrganization'] ?? $this->existsInOrganization
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'path' => $this->path,
            'visibility' => $this->visibility,
            'type' => $this->visibility, // Include type as alias
            'stability' => $this->stability,
            'description' => $this->description,
            'composer' => $this->composer,
            'existsInOrganization' => $this->existsInOrganization,
        ];
    }
}
