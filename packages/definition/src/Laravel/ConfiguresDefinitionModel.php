<?php

declare(strict_types=1);

namespace Moox\Definition\Laravel;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Moox\Definition\Definitions\Field;
use Moox\Definition\Definitions\FieldType;
use Moox\Definition\Definitions\Specifications\ChoiceSpecification;
use Moox\Definition\Definitions\Specifications\DecimalSpecification;
use Moox\Definition\Definitions\Specifications\MoneySpecification;
use Moox\Definition\Definitions\Specifications\SliderSpecification;
use Moox\Definition\Features\Identity\IdentityModel;
use Moox\Definition\Projection\IncompleteProjection;
use Moox\Definition\Resolution\ResolvedEntity;

trait ConfiguresDefinitionModel
{
    /** @var array<class-string, ResolvedEntity> */
    private static array $definitionCache = [];

    public function initializeConfiguresDefinitionModel(): void
    {
        $entity = $this->resolvedDefinition();
        $primary = $entity->primaryField();
        $identifierCount = 0;

        foreach ($entity->fields() as $field) {
            if ($field->type() === FieldType::Id) {
                $identifierCount++;
            }
        }

        if ($primary === null) {
            $reason = $identifierCount === 0
                ? 'has no primary identifier field'
                : 'has more than one primary identifier field';

            throw new IncompleteProjection("Entity [{$entity->name()}] {$reason}.");
        }

        if ($entity->table() === null || $entity->table() === '') {
            throw new IncompleteProjection("Entity [{$entity->name()}] is missing a table name.");
        }

        if ($entity->hasFeature('identity')) {
            (new IdentityModel)->apply($entity);
        }

        $this->table = $entity->table();
        $this->primaryKey = $primary->name();
        $this->incrementing = $primary->attributes()->generator()->value() === 'primary';
        $this->keyType = 'int';
        $this->timestamps = false;
        $this->fillable($this->massAssignable($entity));
        $this->mergeCasts($this->definitionCasts($entity));

        foreach ($entity->fields() as $field) {
            if (! $field->attributes()->hasDefault()) {
                continue;
            }

            $this->attributes[$field->name()] = $field->attributes()->default();
        }
    }

    protected static function bootConfiguresDefinitionModel(): void
    {
        static::creating(function (EloquentModel $model): void {
            if (! $model instanceof static) {
                return;
            }

            (new IdentifierAssignment)->assign($model, $model->resolvedDefinition());
        });

        static::updating(function (EloquentModel $model): void {
            if (! $model instanceof static) {
                return;
            }

            (new WriteGuard)->assert($model, $model->resolvedDefinition());
        });
    }

    abstract public static function definition(): ResolvedEntity;

    public function resolvedDefinition(): ResolvedEntity
    {
        return self::$definitionCache[static::class] ??= static::definition();
    }

    /**
     * @return list<string>
     */
    private function massAssignable(ResolvedEntity $entity): array
    {
        $fillable = [];

        foreach ($entity->fields() as $field) {
            if (! $field->attributes()->systemManaged()) {
                $fillable[] = $field->name();
            }
        }

        foreach ($entity->relations() as $resolved) {
            $foreignKey = $resolved->relation()->keys()->foreignKey();

            if ($resolved->relation()->type()->value === 'belongsTo' && $foreignKey !== null) {
                $fillable[] = $foreignKey;
            }
        }

        return $fillable;
    }

    /**
     * @return array<string, string>
     */
    private function definitionCasts(ResolvedEntity $entity): array
    {
        $casts = [];

        foreach ($entity->fields() as $field) {
            $cast = $this->castFor($field);

            if ($cast !== null) {
                $casts[$field->name()] = $cast;
            }
        }

        return $casts;
    }

    private function castFor(Field $field): ?string
    {
        $specification = $field->specification();

        return match ($field->type()) {
            FieldType::Checkbox, FieldType::Toggle => 'boolean',
            FieldType::Integer, FieldType::BigInteger, FieldType::Id => 'integer',
            FieldType::Float => 'float',
            FieldType::Decimal, FieldType::Percentage => $specification instanceof DecimalSpecification
                ? 'decimal:'.(string) $specification->scale()
                : null,
            FieldType::Money => $specification instanceof MoneySpecification
                ? 'decimal:'.(string) $specification->scale()
                : null,
            FieldType::Date => 'date',
            FieldType::DateTime => UtcDateTimeCast::class,
            FieldType::Json, FieldType::KeyValue, FieldType::Repeater, FieldType::Builder, FieldType::Tags, FieldType::MultiSelect, FieldType::CheckboxList => 'array',
            FieldType::ToggleButtons => $specification instanceof ChoiceSpecification && $specification->multiple() === true ? 'array' : null,
            FieldType::Slider => $specification instanceof SliderSpecification && $specification->range() === true ? 'array' : null,
            default => null,
        };
    }
}
