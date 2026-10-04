<?php

declare(strict_types=1);

namespace Moox\Definition\Resolution;

use Moox\Definition\Definitions\Capability;
use Moox\Definition\Definitions\Constraint;
use Moox\Definition\Definitions\Entity;
use Moox\Definition\Definitions\Feature;
use Moox\Definition\Definitions\Field;
use Moox\Definition\Definitions\Relation;
use Moox\Definition\Definitions\RelationType;
use Moox\Definition\Projection\Storage;
use Moox\Definition\Projection\StorageColumn;
use Moox\Definition\Validation\DefinitionValidator;
use Moox\Definition\Validation\FieldLookup;
use Moox\Definition\Validation\InvalidDefinition;

final class Resolver implements FieldLookup
{
    /** @var array<string, Composition> */
    private array $composed = [];

    public function __construct(
        private readonly Catalog $catalog,
        private readonly DefinitionValidator $validator = new DefinitionValidator,
    ) {
    }

    public function resolve(string $package, string $entity): ResolvedEntity
    {
        $composition = $this->compose($package, $entity);
        $issues = $this->validator->validateStructure(
            $composition->fields,
            $composition->relations,
            $composition->constraints,
            $this,
        );
        array_push($issues, ...$this->validator->validateCapabilities($composition->capabilities));

        if ($issues !== []) {
            throw new InvalidDefinition($issues);
        }

        return $composition->finish($this);
    }

    public function find(string $package, string $entity, string $field): Field|string
    {
        $missing = $this->findEntity($package, $entity);

        if ($missing !== null) {
            return $missing;
        }

        $found = $this->compose($package, $entity)->fields[$field] ?? null;

        if ($found === null) {
            return "Target field [{$field}] is not defined on [{$package}.{$entity}].";
        }

        return $found;
    }

    public function findEntity(string $package, string $entity): ?string
    {
        if (! $this->catalog->hasPackage($package)) {
            return "Target package [{$package}] is not registered.";
        }

        if ($this->catalog->entity($package, $entity) === null) {
            return "Target entity [{$entity}] is not declared on package [{$package}].";
        }

        return null;
    }

    private function compose(string $package, string $entity): Composition
    {
        $key = $package."\0".$entity;

        if (isset($this->composed[$key])) {
            return $this->composed[$key];
        }

        $declaration = $this->catalog->entity($package, $entity);

        if ($declaration === null) {
            $missing = $this->findEntity($package, $entity);

            throw InvalidDefinition::because($missing ?? "Entity [{$entity}] is not declared.");
        }

        $composition = $this->merge($declaration);
        $issues = $this->validator->validateFields($composition->fields);

        if ($issues !== []) {
            throw new InvalidDefinition($issues);
        }

        $this->composed[$key] = $composition;

        return $composition;
    }

    private function merge(Entity $declaration): Composition
    {
        /** @var array<string, Field> $fields */
        $fields = [];
        /** @var array<string, FieldOrigin> $origins */
        $origins = [];
        /** @var array<string, Relation> $relations */
        $relations = [];
        /** @var array<string, FieldOrigin> $relationOrigins */
        $relationOrigins = [];
        /** @var list<Constraint> $constraints */
        $constraints = [];
        /** @var array<string, Capability> $capabilities */
        $capabilities = [];
        /** @var list<string> $replaced */
        $replaced = [];

        foreach ($declaration->features() as $feature) {
            $sandbox = Entity::named($declaration->name());
            $feature->apply($sandbox);

            foreach ($sandbox->fields() as $name => $field) {
                if (isset($fields[$name]) && ! in_array($name, $feature->overrides(), true)) {
                    $previous = $origins[$name]->featureKey() ?? 'domain';

                    throw new CompositionConflict("Field [{$name}] is contributed by feature [{$previous}] and feature [{$feature::key()}]. Declare an explicit override to replace it.");
                }

                $fields[$name] = $field;
                $origins[$name] = FieldOrigin::feature($feature::key());
            }

            foreach ($sandbox->relations() as $name => $relation) {
                if (isset($relations[$name]) && ! in_array($name, $feature->overrides(), true)) {
                    $previous = $relationOrigins[$name]->featureKey() ?? 'domain';

                    throw new CompositionConflict("Relation [{$name}] is contributed by feature [{$previous}] and feature [{$feature::key()}]. Declare an explicit override to replace it.");
                }

                $relations[$name] = $relation;
                $relationOrigins[$name] = FieldOrigin::feature($feature::key());
            }

            array_push($constraints, ...$sandbox->constraints());

            foreach ($sandbox->capabilities() as $name => $capability) {
                if (isset($capabilities[$name])) {
                    throw new CompositionConflict("Capability [{$name}] is declared more than once.");
                }

                $capabilities[$name] = $capability;
            }
        }

        foreach ($declaration->fields() as $name => $field) {
            if (isset($fields[$name])) {
                if (! in_array($name, $declaration->overrides(), true)) {
                    $feature = $origins[$name]->featureKey() ?? 'a feature';

                    throw new CompositionConflict("Field [{$name}] is declared on the entity and contributed by feature [{$feature}]. Declare an explicit override to replace the feature field.");
                }

                $replaced[] = $name;
            }

            $fields[$name] = $field;
            $origins[$name] = FieldOrigin::domain();
        }

        foreach ($declaration->relations() as $name => $relation) {
            if (isset($relations[$name])) {
                if (! in_array($name, $declaration->overrides(), true)) {
                    $feature = $relationOrigins[$name]->featureKey() ?? 'a feature';

                    throw new CompositionConflict("Relation [{$name}] is declared on the entity and contributed by feature [{$feature}]. Declare an explicit override to replace the feature relation.");
                }

                $replaced[] = $name;
            }

            $relations[$name] = $relation;
            $relationOrigins[$name] = FieldOrigin::domain();
        }

        foreach ($declaration->overrides() as $name) {
            if (! in_array($name, $replaced, true)) {
                throw new CompositionConflict("Override [{$name}] does not replace a contributed field or relation.");
            }
        }

        array_push($constraints, ...$declaration->constraints());

        foreach ($declaration->capabilities() as $name => $capability) {
            if (isset($capabilities[$name])) {
                throw new CompositionConflict("Capability [{$name}] is declared more than once.");
            }

            $capabilities[$name] = $capability;
        }

        return new Composition(
            $declaration->name(),
            $declaration->tableName(),
            $fields,
            $origins,
            $relations,
            $relationOrigins,
            $declaration->features(),
            $constraints,
            $capabilities,
        );
    }
}

final class Composition
{
    /**
     * @param  array<string, Field>  $fields
     * @param  array<string, FieldOrigin>  $origins
     * @param  array<string, Relation>  $relations
     * @param  array<string, FieldOrigin>  $relationOrigins
     * @param  list<Feature>  $features
     * @param  list<Constraint>  $constraints
     * @param  array<string, Capability>  $capabilities
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $table,
        public readonly array $fields,
        public readonly array $origins,
        public readonly array $relations,
        public readonly array $relationOrigins,
        public readonly array $features,
        public readonly array $constraints,
        public readonly array $capabilities,
    ) {
    }

    public function finish(FieldLookup $lookup): ResolvedEntity
    {
        $resolved = [];

        foreach ($this->relations as $name => $relation) {
            $resolved[$name] = new ResolvedRelation(
                $relation,
                $this->relationOrigins[$name],
                $this->localColumn($relation, $lookup),
            );
        }

        return new ResolvedEntity(
            $this->name,
            $this->table,
            $this->fields,
            $this->origins,
            $resolved,
            $this->features,
            $this->constraints,
            $this->capabilities,
        );
    }

    private function localColumn(Relation $relation, FieldLookup $lookup): ?StorageColumn
    {
        if ($relation->type() !== RelationType::BelongsTo || $relation->target() === null) {
            return null;
        }

        $target = $lookup->find($relation->target()->package(), $relation->target()->entity(), $relation->target()->field());

        if (! $target instanceof Field) {
            throw InvalidDefinition::because($target);
        }

        return Storage::forReference($target);
    }
}
