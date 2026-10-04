<?php

declare(strict_types=1);

namespace Moox\Definition\Laravel;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Moox\Definition\Definitions\Field;
use Moox\Definition\Definitions\RelationType;
use Moox\Definition\Projection\IncompleteProjection;
use Moox\Definition\Projection\Storage;
use Moox\Definition\Projection\StorageColumn;
use Moox\Definition\Projection\StorageKind;
use Moox\Definition\Projection\UnsupportedProjection;
use Moox\Definition\Resolution\Catalog;
use Moox\Definition\Resolution\ResolvedEntity;
use Moox\Definition\Resolution\ResolvedRelation;

final class Migration
{
    public function apply(Blueprint $table, ResolvedEntity $entity, Catalog $catalog): void
    {
        if ($entity->table() === null || $entity->table() === '') {
            throw new IncompleteProjection("Entity [{$entity->name()}] is missing a table name.");
        }

        foreach ($entity->fields() as $field) {
            $this->addField($table, $field);
        }

        foreach ($entity->relations() as $relation) {
            $this->addRelation($table, $relation, $catalog);
        }

        foreach ($entity->fields() as $field) {
            $this->addScopedUnique($table, $field);
        }

        foreach ($entity->constraints() as $constraint) {
            if ($constraint->isUnique()) {
                $table->unique($constraint->fields());
            }

            if ($constraint->isIndex()) {
                $table->index($constraint->fields());
            }
        }

        foreach ($entity->features() as $feature) {
            $companion = $feature::class.'Migration';

            if (! class_exists($companion)) {
                continue;
            }

            $migration = new $companion;

            if ($migration instanceof FeatureMigration) {
                $migration->apply($table, $entity);
            }
        }
    }

    public function applyPivot(Blueprint $table, ResolvedEntity $owner, ResolvedRelation $resolved, Catalog $catalog): void
    {
        $relation = $resolved->relation();
        $pivot = $relation->pivot();
        $target = $relation->target();

        if ($pivot === null || $target === null || $relation->keys()->localKey() === null) {
            throw new IncompleteProjection("Relation [{$relation->name()}] is missing pivot keys.");
        }

        $localField = $owner->field($relation->keys()->localKey());

        if ($localField === null) {
            throw new IncompleteProjection("localKey [{$relation->keys()->localKey()}] is not defined on [{$owner->name()}].");
        }

        $related = $catalog->resolve($target->package(), $target->entity());
        $relatedField = $related->field($target->field());

        if ($relatedField === null) {
            throw new IncompleteProjection("Target field [{$target->field()}] is not defined on [{$target->package()}.{$target->entity()}].");
        }

        $foreign = $this->column($table, $pivot->foreignPivotKey(), Storage::forReference($localField));
        $foreign->nullable(false);
        $relatedColumn = $this->column($table, $pivot->relatedPivotKey(), Storage::forReference($relatedField));
        $relatedColumn->nullable(false);

        foreach ($pivot->additionalFields() as $field) {
            $this->addField($table, $field);
        }

        if ($pivot->timestamps()) {
            $table->timestamps();
        }
    }

    private function addField(Blueprint $table, Field $field): void
    {
        $column = $this->column($table, $field->name(), Storage::forField($field));
        $attributes = $field->attributes();
        $column->nullable($attributes->nullable());

        if ($attributes->hasDefault()) {
            $column->default($attributes->default());
        }

        if ($attributes->unique() && $attributes->uniqueScope()->isGlobal() && $field->type()->value !== 'id') {
            $column->unique();
        }

        if ($attributes->index() && ! $attributes->unique()) {
            $column->index();
        }
    }

    private function addRelation(Blueprint $table, ResolvedRelation $resolved, Catalog $catalog): void
    {
        $relation = $resolved->relation();

        if (in_array($relation->type(), [RelationType::HasOne, RelationType::HasMany, RelationType::BelongsToMany], true)) {
            return;
        }

        if ($relation->type() !== RelationType::BelongsTo) {
            throw UnsupportedProjection::relation('Laravel migration', $relation->name(), $relation->type()->value);
        }

        $storage = $resolved->localColumn();
        $target = $relation->target();
        $foreignKey = $relation->keys()->foreignKey();
        $ownerKey = $relation->keys()->ownerKey();

        if ($storage === null || $target === null || $foreignKey === null || $ownerKey === null) {
            throw new IncompleteProjection("Relation [{$relation->name()}] is missing foreign key storage.");
        }

        $targetEntity = $catalog->resolve($target->package(), $target->entity());

        if ($targetEntity->table() === null || $targetEntity->table() === '') {
            throw new IncompleteProjection("Target entity [{$target->package()}.{$target->entity()}] is missing a table name.");
        }

        $column = $this->column($table, $foreignKey, $storage);
        $column->nullable($relation->nullable());
        $table->index($foreignKey);
        $table->foreign($foreignKey)->references($ownerKey)->on($targetEntity->table());
    }

    private function addScopedUnique(Blueprint $table, Field $field): void
    {
        $attributes = $field->attributes();

        if (! $attributes->unique() || $attributes->uniqueScope()->isGlobal()) {
            return;
        }

        $table->unique([...$attributes->uniqueScope()->columns(), $field->name()]);
    }

    private function column(Blueprint $table, string $name, StorageColumn $storage): ColumnDefinition
    {
        return match ($storage->kind()) {
            StorageKind::BigIncrements => $table->id($name),
            StorageKind::UnsignedBigInteger => $table->unsignedBigInteger($name),
            StorageKind::Uuid => $table->uuid($name),
            StorageKind::Ulid => $table->ulid($name),
            StorageKind::String => $table->string($name, $this->length($name, $storage)),
            StorageKind::LongText => $table->longText($name),
            StorageKind::Boolean => $table->boolean($name),
            StorageKind::Integer => $table->integer($name),
            StorageKind::BigInteger => $table->bigInteger($name),
            StorageKind::Decimal => $table->decimal($name, $this->precision($name, $storage), $this->scale($name, $storage)),
            StorageKind::Double => $table->double($name),
            StorageKind::Date => $table->date($name),
            StorageKind::Timestamp => $table->timestamp($name),
            StorageKind::Time => $table->time($name),
            StorageKind::Json => $table->json($name),
        };
    }

    private function length(string $name, StorageColumn $storage): int
    {
        return $storage->length() ?? throw new IncompleteProjection("Column [{$name}] is missing a length.");
    }

    private function precision(string $name, StorageColumn $storage): int
    {
        return $storage->precision() ?? throw new IncompleteProjection("Column [{$name}] is missing precision.");
    }

    private function scale(string $name, StorageColumn $storage): int
    {
        return $storage->scale() ?? throw new IncompleteProjection("Column [{$name}] is missing scale.");
    }
}
