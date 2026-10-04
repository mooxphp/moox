<?php

declare(strict_types=1);

namespace Moox\Definition\Validation;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Moox\Definition\Definitions\Capability;
use Moox\Definition\Definitions\ColorFormat;
use Moox\Definition\Definitions\Constraint;
use Moox\Definition\Definitions\EntityReference;
use Moox\Definition\Definitions\Field;
use Moox\Definition\Definitions\FieldType;
use Moox\Definition\Definitions\OptionsSource;
use Moox\Definition\Definitions\Relation;
use Moox\Definition\Definitions\RelationType;
use Moox\Definition\Definitions\Specifications\BuilderSpecification;
use Moox\Definition\Definitions\Specifications\ChoiceSpecification;
use Moox\Definition\Definitions\Specifications\CodeSpecification;
use Moox\Definition\Definitions\Specifications\ColorSpecification;
use Moox\Definition\Definitions\Specifications\DecimalSpecification;
use Moox\Definition\Definitions\Specifications\IconSpecification;
use Moox\Definition\Definitions\Specifications\JsonSpecification;
use Moox\Definition\Definitions\Specifications\KeyValueSpecification;
use Moox\Definition\Definitions\Specifications\MediaReferenceSpecification;
use Moox\Definition\Definitions\Specifications\MediaSpecification;
use Moox\Definition\Definitions\Specifications\MoneySpecification;
use Moox\Definition\Definitions\Specifications\RepeaterSpecification;
use Moox\Definition\Definitions\Specifications\SliderSpecification;
use Moox\Definition\Definitions\Specifications\TaxonomySpecification;
use Moox\Definition\Definitions\Specifications\TextSpecification;
use Moox\Definition\Definitions\ValueType;
use ReflectionEnum;

final class DefinitionValidator
{
    /**
     * @param  array<string, Field>  $fields
     * @return list<DefinitionIssue>
     */
    public function validateFields(array $fields): array
    {
        $issues = [];

        foreach ($fields as $field) {
            array_push($issues, ...$this->validateField($field, 'fields.'.$field->name()));
        }

        return $issues;
    }

    /**
     * @param  array<string, Field>  $localFields
     * @param  array<string, Relation>  $relations
     * @param  list<Constraint>  $constraints
     * @return list<DefinitionIssue>
     */
    public function validateStructure(array $localFields, array $relations, array $constraints, FieldLookup $lookup): array
    {
        $issues = [];

        foreach ($relations as $relation) {
            array_push($issues, ...$this->validateRelation($relation, $localFields, $lookup));
        }

        foreach ($constraints as $index => $constraint) {
            array_push($issues, ...$this->validateConstraint($constraint, $index, $localFields));
        }

        foreach ($localFields as $field) {
            array_push($issues, ...$this->validateScopes($field, $localFields));
            array_push($issues, ...$this->validateTargets($field, $lookup));
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    public function validateField(Field $field, string $path): array
    {
        $issues = [];
        $attributes = $field->attributes();

        if ($field->name() === '') {
            $issues[] = $this->issue($path, 'Field name is required.');
        }

        if ($field->type() === FieldType::Relation) {
            $issues[] = $this->issue($path, 'Relations are declared with addRelation() and explicit keys.');
        }

        if ($attributes->required() && $attributes->nullable()) {
            $issues[] = $this->issue($path, 'A required field cannot be nullable.');
        }

        if (! $attributes->required() && ! $attributes->nullable() && ! $attributes->hasDefault() && ! $attributes->systemManaged() && ! $attributes->isGenerated()) {
            $issues[] = $this->issue($path, 'A field that is neither required nor nullable must declare a default, systemManaged, or generated.');
        }

        if ($attributes->hasDefault()) {
            array_push($issues, ...$this->validateDefault($field, $path));
        }

        if ($attributes->isGenerated()) {
            $generator = $attributes->generator()->value();

            if (! is_string($generator) || $generator === '') {
                $issues[] = $this->issue($path.'.generated', 'A generated field requires a named generator.');
            } elseif (! $this->generatorMatchesType($field->type(), $generator)) {
                $issues[] = $this->issue($path.'.generated', "Generator [{$generator}] is not valid for type [{$field->type()->value}].");
            }
        }

        if ($attributes->unique() && ! $attributes->uniqueScope()->isGlobal() && $attributes->uniqueScope()->columns() === []) {
            $issues[] = $this->issue($path.'.unique', 'A non-global unique scope must name its fields.');
        }

        array_push($issues, ...$this->validateType($field, $path));

        return $issues;
    }

    /**
     * @param  array<string, Capability>  $capabilities
     * @return list<DefinitionIssue>
     */
    public function validateCapabilities(array $capabilities): array
    {
        $issues = [];

        foreach ($capabilities as $capability) {
            array_push($issues, ...$this->validateCapability($capability));
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateType(Field $field, string $path): array
    {
        return match ($field->type()) {
            FieldType::Id => $this->requireAll($path, [
                [$field->attributes()->unique(), 'Type [id] must be unique.'],
                [$field->attributes()->immutable(), 'Type [id] must be immutable.'],
                [$field->attributes()->systemManaged(), 'Type [id] must be systemManaged.'],
                [$field->attributes()->isGenerated() && $field->attributes()->generator()->value() === 'primary', 'Type [id] must be generated with [primary].'],
                [$field->attributes()->nullable() === false, 'Type [id] cannot be nullable.'],
            ]),
            FieldType::Uuid => $this->requireAll($path, [
                [$field->attributes()->unique(), 'Type [uuid] must be unique.'],
                [$field->attributes()->immutable(), 'Type [uuid] must be immutable.'],
            ]),
            FieldType::Ulid => $this->requireAll($path, [
                [$field->attributes()->unique(), 'Type [ulid] must be unique.'],
                [$field->attributes()->immutable(), 'Type [ulid] must be immutable.'],
            ]),
            FieldType::Title => $this->requireAll($path, [
                [$field->attributes()->required(), 'Type [title] must be required.'],
            ]),
            FieldType::Slug => $this->requireAll($path, [
                [$field->attributes()->unique(), 'Type [slug] must be unique.'],
            ]),
            FieldType::Text => $this->validateText($field, $path),
            FieldType::Code => $this->validateCode($field, $path),
            FieldType::Decimal, FieldType::Percentage => $this->validateDecimal($field, $path),
            FieldType::Money => $this->validateMoney($field, $path),
            FieldType::Select, FieldType::Radio, FieldType::MultiSelect, FieldType::CheckboxList => $this->validateChoice($field, $path, false),
            FieldType::ToggleButtons => $this->validateChoice($field, $path, true),
            FieldType::Slider => $this->validateSlider($field, $path),
            FieldType::Color => $this->validateColor($field, $path),
            FieldType::Icon => $this->validateIcon($field, $path),
            FieldType::Image, FieldType::File => $this->validateMediaReference($field, $path),
            FieldType::Media => $this->validateMedia($field, $path),
            FieldType::Json => $this->validateJson($field, $path),
            FieldType::KeyValue => $this->validateKeyValue($field, $path),
            FieldType::Repeater => $this->validateRepeater($field, $path),
            FieldType::Builder => $this->validateBuilder($field, $path),
            FieldType::Taxonomy => $this->validateTaxonomy($field, $path),
            default => [],
        };
    }

    /**
     * @param  list<array{0: bool, 1: string}>  $rules
     * @return list<DefinitionIssue>
     */
    private function requireAll(string $path, array $rules): array
    {
        $issues = [];

        foreach ($rules as [$passes, $message]) {
            if (! $passes) {
                $issues[] = $this->issue($path, $message);
            }
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateText(Field $field, string $path): array
    {
        $specification = $field->specification();
        $maxLength = $specification instanceof TextSpecification ? $specification->maxLength() : null;

        if ($maxLength === null || $maxLength < 1) {
            return [$this->issue($path.'.maxLength', 'Type [text] requires maxLength.')];
        }

        return [];
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateCode(Field $field, string $path): array
    {
        $specification = $field->specification();

        if (! $specification instanceof CodeSpecification || $specification->language() === null) {
            return [$this->issue($path.'.language', 'Type [code] requires a language.')];
        }

        return [];
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateDecimal(Field $field, string $path): array
    {
        $specification = $field->specification();

        if (! $specification instanceof DecimalSpecification || $specification->precision() === null || $specification->scale() === null) {
            return [$this->issue($path, 'Type ['.$field->type()->value.'] requires precision and scale.')];
        }

        return $this->validatePrecision($path, $specification->precision(), $specification->scale());
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateMoney(Field $field, string $path): array
    {
        $specification = $field->specification();
        $issues = [];

        if (! $specification instanceof MoneySpecification) {
            return [$this->issue($path, 'Type [money] requires currency, precision, and scale.')];
        }

        if ($specification->currency() === null || $specification->currency() === '') {
            $issues[] = $this->issue($path.'.currency', 'Type [money] requires currency.');
        }

        if ($specification->precision() === null || $specification->scale() === null) {
            $issues[] = $this->issue($path, 'Type [money] requires precision and scale.');
        } else {
            array_push($issues, ...$this->validatePrecision($path, $specification->precision(), $specification->scale()));
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validatePrecision(string $path, int $precision, int $scale): array
    {
        $issues = [];

        if ($precision < 1) {
            $issues[] = $this->issue($path.'.precision', 'Precision must be at least 1.');
        }

        if ($scale < 0 || $scale > $precision) {
            $issues[] = $this->issue($path.'.scale', 'Scale must be between 0 and precision.');
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateChoice(Field $field, string $path, bool $requiresMultiple): array
    {
        $specification = $field->specification();

        if (! $specification instanceof ChoiceSpecification) {
            return [$this->issue($path, 'Type ['.$field->type()->value.'] requires valueType and options.')];
        }

        $issues = [];

        if ($specification->valueType() === null) {
            $issues[] = $this->issue($path.'.valueType', 'Type ['.$field->type()->value.'] requires valueType.');
        }

        if ($specification->options() === null) {
            $issues[] = $this->issue($path.'.options', 'Type ['.$field->type()->value.'] requires an options source.');
        } else {
            array_push($issues, ...$this->validateOptions($field, $path, $specification));
        }

        if ($requiresMultiple && $specification->multiple() === null) {
            $issues[] = $this->issue($path.'.multiple', 'Type [toggleButtons] requires multiple.');
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateOptions(Field $field, string $path, ChoiceSpecification $specification): array
    {
        $options = $specification->options();

        if ($options === null || $specification->valueType() === null) {
            return [];
        }

        $issues = [];

        if ($options->source() === OptionsSource::Static) {
            $values = $options->values() ?? [];

            foreach ($values as $label => $value) {
                if (! $this->matchesValueType($specification->valueType(), $value)) {
                    $issues[] = $this->issue($path.'.options.'.$label, 'Option value is not valid for valueType ['.$specification->valueType()->value.'].');
                }
            }
        }

        if ($options->source() !== OptionsSource::Static && ($options->reference() === null || $options->reference() === '')) {
            $issues[] = $this->issue($path.'.options', 'Options source ['.$options->source()->value.'] requires a reference.');
        }

        if ($specification->valueType() === ValueType::Enum) {
            if ($options->source() !== OptionsSource::Enum) {
                $issues[] = $this->issue($path.'.options', 'valueType [enum] requires an enum options source.');
            }

            array_push($issues, ...$this->validateBackedEnum($path.'.options', $options->reference()));
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateBackedEnum(string $path, ?string $class): array
    {
        if ($class === null || $class === '') {
            return [$this->issue($path, 'An enum class is required.')];
        }

        if (! enum_exists($class)) {
            return [$this->issue($path, "Enum [{$class}] does not exist.")];
        }

        if ((new ReflectionEnum($class))->getBackingType() === null) {
            return [$this->issue($path, "Enum [{$class}] must be backed.")];
        }

        return [];
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateSlider(Field $field, string $path): array
    {
        $specification = $field->specification();

        if (! $specification instanceof SliderSpecification) {
            return [$this->issue($path, 'Type [slider] requires valueType, min, max, step, and range.')];
        }

        $issues = [];

        if ($specification->valueType() !== ValueType::Integer && $specification->valueType() !== ValueType::Decimal) {
            $issues[] = $this->issue($path.'.valueType', 'Type [slider] requires valueType integer or decimal.');
        }

        foreach (['min' => $specification->min(), 'max' => $specification->max(), 'step' => $specification->step()] as $name => $value) {
            if ($value === null || $value === '') {
                $issues[] = $this->issue($path.'.'.$name, "Type [slider] requires {$name}.");
            }
        }

        if ($specification->range() === null) {
            $issues[] = $this->issue($path.'.range', 'Type [slider] requires range.');
        }

        if ($specification->valueType() === ValueType::Decimal && ($specification->precision() === null || $specification->scale() === null)) {
            $issues[] = $this->issue($path, 'A decimal slider requires precision and scale.');
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateColor(Field $field, string $path): array
    {
        $specification = $field->specification();

        if (! $specification instanceof ColorSpecification || $specification->format() === null) {
            return [$this->issue($path.'.format', 'Type [color] requires a format.')];
        }

        return [];
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateIcon(Field $field, string $path): array
    {
        $specification = $field->specification();
        $issues = [];

        if (! $specification instanceof IconSpecification || $specification->provider() === null || $specification->provider() === '') {
            $issues[] = $this->issue($path.'.provider', 'Type [icon] requires a provider.');
        }

        if (! $specification instanceof IconSpecification || $specification->set() === null || $specification->set() === '') {
            $issues[] = $this->issue($path.'.set', 'Type [icon] requires a set.');
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateMediaReference(Field $field, string $path): array
    {
        $specification = $field->specification();

        if (! $specification instanceof MediaReferenceSpecification || $specification->target() === null) {
            return [$this->issue($path.'.target', 'Type ['.$field->type()->value.'] requires a media target.')];
        }

        return $this->validateReferenceShape($path.'.target', $specification->target());
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateMedia(Field $field, string $path): array
    {
        $specification = $field->specification();
        $issues = [];

        if (! $specification instanceof MediaSpecification || $specification->target() === null) {
            $issues[] = $this->issue($path.'.mediaTarget', 'Type [media] requires mediaTarget.');
        } else {
            array_push($issues, ...$this->validateReferenceShape($path.'.mediaTarget', $specification->target()));
        }

        if (! $specification instanceof MediaSpecification || $specification->cardinality() === null) {
            $issues[] = $this->issue($path.'.cardinality', 'Type [media] requires cardinality.');
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateJson(Field $field, string $path): array
    {
        $specification = $field->specification();

        if (! $specification instanceof JsonSpecification || $specification->schema() === null || $specification->schema() === []) {
            return [$this->issue($path.'.schema', 'Type [json] requires a schema.')];
        }

        if (! $this->isSerializable($specification->schema())) {
            return [$this->issue($path.'.schema', 'A json schema must be deterministically serializable.')];
        }

        return [];
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateKeyValue(Field $field, string $path): array
    {
        $specification = $field->specification();
        $issues = [];

        if (! $specification instanceof KeyValueSpecification || $specification->keyType() === null) {
            $issues[] = $this->issue($path.'.keyType', 'Type [keyValue] requires keyType.');
        } elseif ($specification->keyType() === ValueType::Enum) {
            $issues[] = $this->issue($path.'.keyType', 'Type [keyValue] does not accept valueType [enum].');
        }

        if (! $specification instanceof KeyValueSpecification || $specification->valueType() === null) {
            $issues[] = $this->issue($path.'.valueType', 'Type [keyValue] requires valueType.');
        } elseif ($specification->valueType() === ValueType::Enum) {
            $issues[] = $this->issue($path.'.valueType', 'Type [keyValue] does not accept valueType [enum].');
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateRepeater(Field $field, string $path): array
    {
        $specification = $field->specification();

        if (! $specification instanceof RepeaterSpecification || $specification->fields() === null || $specification->fields() === []) {
            return [$this->issue($path, 'Type [repeater] requires child field definitions.')];
        }

        $issues = [];

        foreach ($specification->fields() as $name => $child) {
            if ($child->name() !== $name) {
                $issues[] = $this->issue($path.'.'.$name, 'Repeater child name does not match its field name.');
            }

            array_push($issues, ...$this->validateField($child, $path.'.'.$name));
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateBuilder(Field $field, string $path): array
    {
        $specification = $field->specification();

        if (! $specification instanceof BuilderSpecification || $specification->blocks() === null || $specification->blocks() === []) {
            return [$this->issue($path, 'Type [builder] requires block definitions.')];
        }

        $issues = [];
        $names = [];

        foreach ($specification->blocks() as $block) {
            if ($block->name() === '' || in_array($block->name(), $names, true)) {
                $issues[] = $this->issue($path.'.'.$block->name(), 'Builder block names must be present and unique.');
            }

            $names[] = $block->name();

            if ($block->fields() === []) {
                $issues[] = $this->issue($path.'.'.$block->name(), 'A builder block requires field definitions.');
            }

            foreach ($block->fields() as $name => $child) {
                array_push($issues, ...$this->validateField($child, $path.'.'.$block->name().'.'.$name));
            }
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateTaxonomy(Field $field, string $path): array
    {
        $specification = $field->specification();

        if (! $specification instanceof TaxonomySpecification) {
            return [$this->issue($path, 'Type [taxonomy] requires target, cardinality, hierarchy, customTerms, and sortable.')];
        }

        $issues = [];

        if ($specification->target() === null) {
            $issues[] = $this->issue($path.'.target', 'Type [taxonomy] requires a target.');
        } else {
            array_push($issues, ...$this->validateReferenceShape($path.'.target', $specification->target()));
        }

        if ($specification->cardinality() === null) {
            $issues[] = $this->issue($path.'.cardinality', 'Type [taxonomy] requires cardinality.');
        }

        if ($specification->hierarchy() === null) {
            $issues[] = $this->issue($path.'.hierarchy', 'Type [taxonomy] requires hierarchy.');
        }

        if ($specification->customTerms() === null) {
            $issues[] = $this->issue($path.'.customTerms', 'Type [taxonomy] requires customTerms.');
        }

        if ($specification->sortable() === null) {
            $issues[] = $this->issue($path.'.sortable', 'Type [taxonomy] requires sortable.');
        }

        return $issues;
    }

    /**
     * @param  array<string, Field>  $localFields
     * @return list<DefinitionIssue>
     */
    private function validateRelation(Relation $relation, array $localFields, FieldLookup $lookup): array
    {
        $path = 'relations.'.$relation->name();
        $issues = [];

        if ($relation->name() === '') {
            $issues[] = $this->issue($path, 'Relation name is required.');
        }

        foreach ($this->missingKeys($relation) as $key) {
            $issues[] = $this->issue($path.'.'.$key, "Relation type [{$relation->type()->value}] requires [{$key}].");
        }

        $target = $relation->target();

        if ($target !== null) {
            array_push($issues, ...$this->validateReferenceShape($path.'.target', $target));
            array_push($issues, ...$this->validateRelationTarget($relation, $target, $path, $lookup));
        }

        $through = $relation->through();

        if ($through !== null) {
            array_push($issues, ...$this->validateReferenceShape($path.'.through', $through));
            $found = $lookup->find($through->package(), $through->entity(), $through->field());

            if (is_string($found)) {
                $issues[] = $this->issue($path.'.through', $found);
            }
        }

        if ($relation->type() === RelationType::BelongsTo && $target !== null && $relation->keys()->ownerKey() !== null && $relation->keys()->ownerKey() !== $target->field()) {
            $issues[] = $this->issue($path.'.ownerKey', "ownerKey [{$relation->keys()->ownerKey()}] does not match the referenced target field [{$target->field()}].");
        }

        if (in_array($relation->type(), [RelationType::HasOne, RelationType::HasMany], true) && $target !== null && $relation->keys()->foreignKey() !== null && $relation->keys()->foreignKey() !== $target->field()) {
            $issues[] = $this->issue($path.'.foreignKey', "foreignKey [{$relation->keys()->foreignKey()}] does not match the referenced target field [{$target->field()}].");
        }

        if (in_array($relation->type(), [RelationType::BelongsToMany, RelationType::MorphToMany, RelationType::MorphedByMany], true) && $target !== null && $relation->keys()->relatedKey() !== null && $relation->keys()->relatedKey() !== $target->field()) {
            $issues[] = $this->issue($path.'.relatedKey', "relatedKey [{$relation->keys()->relatedKey()}] does not match the referenced target field [{$target->field()}].");
        }

        $localKey = $relation->keys()->localKey();

        if ($localKey !== null && ! isset($localFields[$localKey])) {
            $issues[] = $this->issue($path.'.localKey', "localKey [{$localKey}] is not defined on this entity.");
        }

        if ($relation->pivot() !== null) {
            array_push($issues, ...$this->validatePivot($relation, $path.'.pivot'));
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateRelationTarget(Relation $relation, EntityReference $target, string $path, FieldLookup $lookup): array
    {
        if (in_array($relation->type(), [RelationType::HasOne, RelationType::HasMany], true)) {
            $missing = $lookup->findEntity($target->package(), $target->entity());

            return $missing === null ? [] : [$this->issue($path.'.target', $missing)];
        }

        $found = $lookup->find($target->package(), $target->entity(), $target->field());

        return is_string($found) ? [$this->issue($path.'.target', $found)] : [];
    }

    /**
     * @return list<string>
     */
    private function missingKeys(Relation $relation): array
    {
        $keys = $relation->keys();
        $required = match ($relation->type()) {
            RelationType::BelongsTo => ['target', 'foreignKey', 'ownerKey'],
            RelationType::HasOne, RelationType::HasMany => ['target', 'foreignKey', 'localKey'],
            RelationType::BelongsToMany => ['target', 'localKey', 'relatedKey', 'pivot'],
            RelationType::HasOneThrough, RelationType::HasManyThrough => ['target', 'through', 'foreignKey', 'throughKey', 'localKey', 'relatedKey'],
            RelationType::MorphTo => ['morphName', 'morphType', 'morphId'],
            RelationType::MorphOne, RelationType::MorphMany => ['target', 'morphName', 'morphType', 'morphId', 'localKey'],
            RelationType::MorphToMany, RelationType::MorphedByMany => ['target', 'morphName', 'morphType', 'morphId', 'relatedKey', 'pivot'],
        };

        $present = [
            'target' => $relation->target() !== null,
            'through' => $relation->through() !== null,
            'pivot' => $relation->pivot() !== null,
            'foreignKey' => $this->filled($keys->foreignKey()),
            'ownerKey' => $this->filled($keys->ownerKey()),
            'localKey' => $this->filled($keys->localKey()),
            'relatedKey' => $this->filled($keys->relatedKey()),
            'throughKey' => $this->filled($keys->throughKey()),
            'morphName' => $this->filled($keys->morphName()),
            'morphType' => $this->filled($keys->morphType()),
            'morphId' => $this->filled($keys->morphId()),
        ];

        $missing = [];

        foreach ($required as $key) {
            if ($present[$key] !== true) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validatePivot(Relation $relation, string $path): array
    {
        $pivot = $relation->pivot();

        if ($pivot === null) {
            return [];
        }

        $issues = [];

        foreach (['table' => $pivot->table(), 'foreignPivotKey' => $pivot->foreignPivotKey(), 'relatedPivotKey' => $pivot->relatedPivotKey()] as $name => $value) {
            if ($value === '') {
                $issues[] = $this->issue($path.'.'.$name, "Pivot {$name} is required.");
            }
        }

        foreach ($pivot->additionalFields() as $field) {
            array_push($issues, ...$this->validateField($field, $path.'.additionalFields.'.$field->name()));
        }

        return $issues;
    }

    /**
     * @param  array<string, Field>  $fields
     * @return list<DefinitionIssue>
     */
    private function validateConstraint(Constraint $constraint, int $index, array $fields): array
    {
        $path = 'constraints.'.$index;
        $issues = [];

        if (count($constraint->fields()) < 2) {
            $issues[] = $this->issue($path, 'Composite constraints belong on the entity and require multiple fields.');
        }

        foreach ($constraint->fields() as $name) {
            if (! isset($fields[$name])) {
                $issues[] = $this->issue($path, "Constraint field [{$name}] is not defined.");
            }
        }

        return $issues;
    }

    /**
     * @param  array<string, Field>  $fields
     * @return list<DefinitionIssue>
     */
    private function validateScopes(Field $field, array $fields): array
    {
        if (! $field->attributes()->unique() || $field->attributes()->uniqueScope()->isGlobal()) {
            return [];
        }

        $issues = [];

        foreach ($field->attributes()->uniqueScope()->columns() as $name) {
            if (! isset($fields[$name])) {
                $issues[] = $this->issue('fields.'.$field->name().'.unique', "Unique scope field [{$name}] is not defined.");
            }
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateTargets(Field $field, FieldLookup $lookup): array
    {
        $reference = match (true) {
            $field->specification() instanceof MediaReferenceSpecification => $field->specification()->target(),
            $field->specification() instanceof MediaSpecification => $field->specification()->target(),
            $field->specification() instanceof TaxonomySpecification => $field->specification()->target(),
            default => null,
        };

        if (! $reference instanceof EntityReference) {
            return [];
        }

        $found = $lookup->find($reference->package(), $reference->entity(), $reference->field());

        if (is_string($found)) {
            return [$this->issue('fields.'.$field->name().'.target', $found)];
        }

        return [];
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateReferenceShape(string $path, EntityReference $reference): array
    {
        $issues = [];

        foreach (['package' => $reference->package(), 'entity' => $reference->entity(), 'field' => $reference->field()] as $name => $value) {
            if ($value === '') {
                $issues[] = $this->issue($path.'.'.$name, "Referenced {$name} is required.");
            }
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateCapability(Capability $capability): array
    {
        $path = 'capabilities.'.$capability->name();
        $issues = [];

        if ($capability->name() === '') {
            $issues[] = $this->issue($path, 'Capability name is required.');
        }

        foreach ($capability->input() as $parameter) {
            if ($parameter->name() === '' || $parameter->type() === '') {
                $issues[] = $this->issue($path.'.input', 'Capability input parameters require a name and a type.');
            }
        }

        return $issues;
    }

    /**
     * @return list<DefinitionIssue>
     */
    private function validateDefault(Field $field, string $path): array
    {
        $default = $field->attributes()->default();

        if (! $this->isSerializable($default)) {
            return [$this->issue($path.'.default', 'A default must be deterministically serializable.')];
        }

        if ($default === null && ! $field->attributes()->nullable()) {
            return [$this->issue($path.'.default', 'A null default requires nullable.')];
        }

        if ($default === null) {
            return [];
        }

        $valid = match ($field->type()) {
            FieldType::Checkbox, FieldType::Toggle => is_bool($default),
            FieldType::Integer, FieldType::BigInteger => is_int($default),
            FieldType::Float => is_int($default) || is_float($default),
            FieldType::Decimal, FieldType::Percentage, FieldType::Money => $this->isExactDecimal($default),
            FieldType::Text, FieldType::Textarea, FieldType::RichText, FieldType::Markdown, FieldType::Code, FieldType::Password, FieldType::Tel, FieldType::Title, FieldType::Slug => is_string($default),
            FieldType::Email => is_string($default) && filter_var($default, FILTER_VALIDATE_EMAIL) !== false,
            FieldType::Url, FieldType::MediaUrl => is_string($default) && filter_var($default, FILTER_VALIDATE_URL) !== false,
            FieldType::Date => is_string($default) && $this->isDate($default),
            FieldType::DateTime => is_string($default) && $this->isUtcDateTime($default),
            FieldType::Time => is_string($default) && $this->isTime($default),
            FieldType::Uuid => is_string($default) && preg_match('/^[0-9a-fA-F-]{36}$/', $default) === 1,
            FieldType::Ulid => is_string($default) && preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $default) === 1,
            FieldType::Color => $this->colorMatches($field, $default),
            default => true,
        };

        if (! $valid) {
            return [$this->issue($path.'.default', 'Default is not valid for type ['.$field->type()->value.'].')];
        }

        if ($field->type() === FieldType::Text && is_string($default)) {
            $specification = $field->specification();
            $maxLength = $specification instanceof TextSpecification ? $specification->maxLength() : null;

            if ($maxLength !== null && strlen($default) > $maxLength) {
                return [$this->issue($path.'.default', 'Default exceeds maxLength.')];
            }
        }

        return [];
    }

    private function colorMatches(Field $field, mixed $default): bool
    {
        if (! is_string($default)) {
            return false;
        }

        $specification = $field->specification();

        if (! $specification instanceof ColorSpecification || $specification->format() === null) {
            return false;
        }

        return match ($specification->format()) {
            ColorFormat::Hex => preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $default) === 1,
            ColorFormat::Rgb => preg_match('/^rgb\(\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*\)$/', $default) === 1,
            ColorFormat::Rgba => preg_match('/^rgba\(\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:25[0-5]|2[0-4]\d|1?\d?\d)\s*,\s*(?:0|1|0?\.\d+)\s*\)$/', $default) === 1,
            ColorFormat::Hsl => preg_match('/^hsl\(\s*\d{1,3}\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*\)$/', $default) === 1,
            ColorFormat::Hsla => preg_match('/^hsla\(\s*\d{1,3}\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*,\s*(?:0|1|0?\.\d+)\s*\)$/', $default) === 1,
        };
    }

    private function matchesValueType(ValueType $valueType, mixed $value): bool
    {
        return match ($valueType) {
            ValueType::String, ValueType::Uuid, ValueType::Ulid => is_string($value),
            ValueType::Integer => is_int($value),
            ValueType::Decimal => $this->isExactDecimal($value),
            ValueType::Boolean => is_bool($value),
            ValueType::Enum => is_string($value) || is_int($value),
        };
    }

    private function generatorMatchesType(FieldType $type, string $generator): bool
    {
        return match ($generator) {
            'primary' => $type === FieldType::Id,
            'uuid' => $type === FieldType::Uuid,
            'ulid' => $type === FieldType::Ulid,
            default => $type !== FieldType::Id,
        };
    }

    private function isExactDecimal(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && preg_match('/^-?\d+(?:\.\d+)?$/', $value) === 1);
    }

    private function isDate(string $value): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $parsed !== false && $parsed->format('Y-m-d') === $value;
    }

    private function isTime(string $value): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!H:i:s', $value);

        return $parsed !== false && $parsed->format('H:i:s') === $value;
    }

    private function isUtcDateTime(string $value): bool
    {
        $utc = new DateTimeZone('UTC');
        $plain = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, $utc);

        if ($plain !== false && $plain->format('Y-m-d H:i:s') === $value) {
            return true;
        }

        $zulu = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, $utc);

        if ($zulu !== false && $zulu->format('Y-m-d\TH:i:s\Z') === $value) {
            return true;
        }

        $offset = DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $value);

        return $offset !== false && $offset->getOffset() === 0 && $offset->format(DateTimeImmutable::ATOM) === $value;
    }

    private function isSerializable(mixed $value): bool
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return true;
        }

        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if ($item instanceof Closure || ! $this->isSerializable($item)) {
                return false;
            }
        }

        return true;
    }

    private function filled(?string $value): bool
    {
        return $value !== null && $value !== '';
    }

    private function issue(string $path, string $message): DefinitionIssue
    {
        return new DefinitionIssue($path, $message);
    }
}
