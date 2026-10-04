<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

use Moox\Definition\Validation\InvalidDefinition;

final class Entity
{
    /** @var array<string, Field> */
    private array $fields = [];

    /** @var array<string, Relation> */
    private array $relations = [];

    /** @var list<Feature> */
    private array $features = [];

    /** @var list<string> */
    private array $overrides = [];

    /** @var list<Constraint> */
    private array $constraints = [];

    /** @var array<string, Capability> */
    private array $capabilities = [];

    private ?string $table = null;

    private function __construct(
        private readonly string $name,
    ) {
    }

    public static function named(string $name): self
    {
        return new self($name);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function table(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function tableName(): ?string
    {
        return $this->table;
    }

    public function addField(Field $field): self
    {
        if (isset($this->fields[$field->name()])) {
            throw InvalidDefinition::because("Field [{$field->name()}] is already declared on [{$this->name}].");
        }

        $this->fields[$field->name()] = $field;

        return $this;
    }

    public function addRelation(Relation $relation): self
    {
        if (isset($this->relations[$relation->name()])) {
            throw InvalidDefinition::because("Relation [{$relation->name()}] is already declared on [{$this->name}].");
        }

        $this->relations[$relation->name()] = $relation;

        return $this;
    }

    public function addFeature(Feature $feature): self
    {
        foreach ($this->features as $existing) {
            if ($existing::key() === $feature::key()) {
                throw InvalidDefinition::because("Feature [{$feature::key()}] is already declared on [{$this->name}].");
            }
        }

        $this->features[] = $feature;

        return $this;
    }

    public function addConstraint(Constraint $constraint): self
    {
        $this->constraints[] = $constraint;

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

    public function override(string $field): self
    {
        if (! in_array($field, $this->overrides, true)) {
            $this->overrides[] = $field;
        }

        return $this;
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

    /**
     * @return array<string, Relation>
     */
    public function relations(): array
    {
        return $this->relations;
    }

    public function relation(string $name): ?Relation
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

    /**
     * @return list<string>
     */
    public function overrides(): array
    {
        return $this->overrides;
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
}
