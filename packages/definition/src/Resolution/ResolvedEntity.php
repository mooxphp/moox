<?php

declare(strict_types=1);

namespace Moox\Definition\Resolution;

use Moox\Definition\Definitions\Capability;
use Moox\Definition\Definitions\Constraint;
use Moox\Definition\Definitions\Feature;
use Moox\Definition\Definitions\Field;
use Moox\Definition\Definitions\FieldType;

final class ResolvedEntity
{
    /**
     * @param  array<string, Field>  $fields
     * @param  array<string, FieldOrigin>  $origins
     * @param  array<string, ResolvedRelation>  $relations
     * @param  list<Feature>  $features
     * @param  list<Constraint>  $constraints
     * @param  array<string, Capability>  $capabilities
     */
    public function __construct(
        private readonly string $name,
        private readonly ?string $table,
        private readonly array $fields,
        private readonly array $origins,
        private readonly array $relations,
        private readonly array $features,
        private readonly array $constraints,
        private readonly array $capabilities,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function table(): ?string
    {
        return $this->table;
    }

    /**
     * @return array<string, Field>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    public function field(string $name): ?Field
    {
        return $this->fields[$name] ?? null;
    }

    public function origin(string $name): ?FieldOrigin
    {
        return $this->origins[$name] ?? null;
    }

    /**
     * @return array<string, ResolvedRelation>
     */
    public function relations(): array
    {
        return $this->relations;
    }

    public function relation(string $name): ?ResolvedRelation
    {
        return $this->relations[$name] ?? null;
    }

    /**
     * @return list<Feature>
     */
    public function features(): array
    {
        return $this->features;
    }

    public function hasFeature(string $key): bool
    {
        foreach ($this->features as $feature) {
            if ($feature::key() === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Constraint>
     */
    public function constraints(): array
    {
        return $this->constraints;
    }

    /**
     * @return array<string, Capability>
     */
    public function capabilities(): array
    {
        return $this->capabilities;
    }

    public function primaryField(): ?Field
    {
        $primary = null;

        foreach ($this->fields as $field) {
            if ($field->type() !== FieldType::Id) {
                continue;
            }

            if ($primary !== null) {
                return null;
            }

            $primary = $field;
        }

        return $primary;
    }
}
