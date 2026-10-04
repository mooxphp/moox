<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

use Moox\Definition\Validation\InvalidDefinition;

final class Package
{
    /** @var array<string, Entity> */
    private array $entities = [];

    /** @var array<string, Capability> */
    private array $capabilities = [];

    public function __construct(
        private readonly string $name,
        private readonly ?string $displayName = null,
        private readonly ?string $description = null,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function displayName(): ?string
    {
        return $this->displayName;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function addEntity(Entity $entity): self
    {
        if (isset($this->entities[$entity->name()])) {
            throw InvalidDefinition::because("Entity [{$entity->name()}] is already declared on [{$this->name}].");
        }

        $this->entities[$entity->name()] = $entity;

        return $this;
    }

    public function addCapability(Capability $capability): self
    {
        if (isset($this->capabilities[$capability->name()])) {
            throw InvalidDefinition::because("Capability [{$capability->name()}] is already declared on [{$this->name}].");
        }

        $this->capabilities[$capability->name()] = $capability;

        return $this;
    }

    public function entity(string $name): ?Entity
    {
        return $this->entities[$name] ?? null;
    }

    /**
     * @return array<string, Entity>
     */
    public function entities(): array
    {
        return $this->entities;
    }

    /**
     * @return array<string, Capability>
     */
    public function capabilities(): array
    {
        return $this->capabilities;
    }
}
