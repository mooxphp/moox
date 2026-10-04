<?php

declare(strict_types=1);

namespace Moox\Definition\Projection;

use Moox\Definition\Definitions\Field;
use Moox\Definition\Definitions\FieldType;
use Moox\Definition\Definitions\Specifications\ChoiceSpecification;
use Moox\Definition\Definitions\Specifications\DecimalSpecification;
use Moox\Definition\Definitions\Specifications\MoneySpecification;
use Moox\Definition\Definitions\Specifications\SliderSpecification;
use Moox\Definition\Definitions\Specifications\TextSpecification;
use Moox\Definition\Definitions\ValueType;
use ReflectionEnum;

final class Storage
{
    public static function forField(Field $field): StorageColumn
    {
        return match ($field->type()) {
            FieldType::Id => new StorageColumn(StorageKind::BigIncrements),
            FieldType::Uuid => new StorageColumn(StorageKind::Uuid),
            FieldType::Ulid => new StorageColumn(StorageKind::Ulid),
            FieldType::Title, FieldType::Slug, FieldType::Email, FieldType::Password => new StorageColumn(StorageKind::String, 255),
            FieldType::Url, FieldType::MediaUrl => new StorageColumn(StorageKind::String, 2048),
            FieldType::Tel => new StorageColumn(StorageKind::String, 64),
            FieldType::Text => self::text($field),
            FieldType::Textarea, FieldType::RichText, FieldType::Markdown, FieldType::Code => new StorageColumn(StorageKind::LongText),
            FieldType::Checkbox, FieldType::Toggle => new StorageColumn(StorageKind::Boolean),
            FieldType::Integer => new StorageColumn(StorageKind::Integer),
            FieldType::BigInteger => new StorageColumn(StorageKind::BigInteger),
            FieldType::Decimal, FieldType::Percentage => self::decimal($field),
            FieldType::Float => new StorageColumn(StorageKind::Double),
            FieldType::Money => self::money($field),
            FieldType::Date => new StorageColumn(StorageKind::Date),
            FieldType::DateTime => new StorageColumn(StorageKind::Timestamp),
            FieldType::Time => new StorageColumn(StorageKind::Time),
            FieldType::Color => new StorageColumn(StorageKind::String, 32),
            FieldType::Icon => new StorageColumn(StorageKind::String, 255),
            FieldType::Json, FieldType::KeyValue, FieldType::Repeater, FieldType::Builder, FieldType::Tags, FieldType::MultiSelect, FieldType::CheckboxList => new StorageColumn(StorageKind::Json),
            FieldType::Select, FieldType::Radio => self::choice($field, false),
            FieldType::ToggleButtons => self::toggleButtons($field),
            FieldType::Slider => self::slider($field),
            FieldType::Image, FieldType::File, FieldType::Media, FieldType::Taxonomy, FieldType::Relation => throw UnsupportedProjection::field('storage', $field->name(), $field->type()->value),
        };
    }

    public static function forReference(Field $field): StorageColumn
    {
        $column = self::forField($field);

        if ($column->kind() === StorageKind::BigIncrements) {
            return new StorageColumn(StorageKind::UnsignedBigInteger);
        }

        return $column;
    }

    private static function text(Field $field): StorageColumn
    {
        $specification = $field->specification();
        $length = $specification instanceof TextSpecification ? $specification->maxLength() : null;

        if ($length === null) {
            throw UnsupportedProjection::field('storage', $field->name(), $field->type()->value);
        }

        return new StorageColumn(StorageKind::String, $length);
    }

    private static function decimal(Field $field): StorageColumn
    {
        $specification = $field->specification();

        if (! $specification instanceof DecimalSpecification || $specification->precision() === null || $specification->scale() === null) {
            throw UnsupportedProjection::field('storage', $field->name(), $field->type()->value);
        }

        return new StorageColumn(StorageKind::Decimal, null, $specification->precision(), $specification->scale());
    }

    private static function money(Field $field): StorageColumn
    {
        $specification = $field->specification();

        if (! $specification instanceof MoneySpecification || $specification->precision() === null || $specification->scale() === null) {
            throw UnsupportedProjection::field('storage', $field->name(), $field->type()->value);
        }

        return new StorageColumn(StorageKind::Decimal, null, $specification->precision(), $specification->scale());
    }

    private static function choice(Field $field, bool $multiple): StorageColumn
    {
        if ($multiple) {
            return new StorageColumn(StorageKind::Json);
        }

        $specification = $field->specification();

        if (! $specification instanceof ChoiceSpecification || $specification->valueType() === null) {
            throw UnsupportedProjection::field('storage', $field->name(), $field->type()->value);
        }

        return self::forValueType($field, $specification->valueType(), $specification->options()?->reference());
    }

    private static function toggleButtons(Field $field): StorageColumn
    {
        $specification = $field->specification();

        if (! $specification instanceof ChoiceSpecification) {
            throw UnsupportedProjection::field('storage', $field->name(), $field->type()->value);
        }

        return self::choice($field, $specification->multiple() === true);
    }

    private static function slider(Field $field): StorageColumn
    {
        $specification = $field->specification();

        if (! $specification instanceof SliderSpecification || $specification->valueType() === null || $specification->range() === null) {
            throw UnsupportedProjection::field('storage', $field->name(), $field->type()->value);
        }

        if ($specification->range()) {
            return new StorageColumn(StorageKind::Json);
        }

        if ($specification->valueType() === ValueType::Integer) {
            return new StorageColumn(StorageKind::Integer);
        }

        if ($specification->precision() === null || $specification->scale() === null) {
            throw UnsupportedProjection::field('storage', $field->name(), $field->type()->value);
        }

        return new StorageColumn(StorageKind::Decimal, null, $specification->precision(), $specification->scale());
    }

    private static function forValueType(Field $field, ValueType $valueType, ?string $enumClass): StorageColumn
    {
        return match ($valueType) {
            ValueType::String => new StorageColumn(StorageKind::String, 255),
            ValueType::Integer => new StorageColumn(StorageKind::Integer),
            ValueType::Decimal => new StorageColumn(StorageKind::String, 255),
            ValueType::Boolean => new StorageColumn(StorageKind::Boolean),
            ValueType::Uuid => new StorageColumn(StorageKind::Uuid),
            ValueType::Ulid => new StorageColumn(StorageKind::Ulid),
            ValueType::Enum => self::enumColumn($field, $enumClass),
        };
    }

    private static function enumColumn(Field $field, ?string $enumClass): StorageColumn
    {
        if ($enumClass === null || ! enum_exists($enumClass)) {
            throw UnsupportedProjection::field('storage', $field->name(), $field->type()->value);
        }

        $backing = (new ReflectionEnum($enumClass))->getBackingType();

        if ($backing === null) {
            throw UnsupportedProjection::field('storage', $field->name(), $field->type()->value);
        }

        return $backing->getName() === 'int'
            ? new StorageColumn(StorageKind::Integer)
            : new StorageColumn(StorageKind::String, 255);
    }
}
