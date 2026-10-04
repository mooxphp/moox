<?php

declare(strict_types=1);

namespace Moox\Definition\Resolution;

use Moox\Definition\Definitions\Entity;
use Moox\Definition\Definitions\Package;
use Moox\Definition\Validation\InvalidDefinition;

final class Catalog
{
    /** @var array<string, Package> */
    private array $packages = [];

    public function add(Package $package): self
    {
        if (isset($this->packages[$package->name()])) {
            throw InvalidDefinition::because("Package [{$package->name()}] is already registered.");
        }

        $this->packages[$package->name()] = $package;

        return $this;
    }

    public function hasPackage(string $name): bool
    {
        return isset($this->packages[$name]);
    }

    public function package(string $name): ?Package
    {
        return $this->packages[$name] ?? null;
    }

    public function entity(string $package, string $entity): ?Entity
    {
        if (! isset($this->packages[$package])) {
            return null;
        }

        return $this->packages[$package]->entity($entity);
    }

    public function resolve(string $package, string $entity): ResolvedEntity
    {
        return (new Resolver($this))->resolve($package, $entity);
    }
}
