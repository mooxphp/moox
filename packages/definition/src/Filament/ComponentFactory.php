<?php

declare(strict_types=1);

namespace Moox\Definition\Filament;

use BackedEnum;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Moox\Definition\Definitions\Field;
use Moox\Definition\Definitions\FieldType;
use Moox\Definition\Definitions\OptionsSource;
use Moox\Definition\Definitions\Relation;
use Moox\Definition\Definitions\Specifications\ChoiceSpecification;
use Moox\Definition\Projection\IncompleteProjection;
use Moox\Definition\Projection\Storage;
use Moox\Definition\Projection\StorageKind;
use Moox\Definition\Projection\UnsupportedProjection;
use ReflectionProperty;

final class ComponentFactory
{
    public function formComponent(Field $field): Component
    {
        $component = $this->makeFormComponent($field);
        $this->applyField($field, $component);

        return $component;
    }

    public function tableColumn(Field $field): Column
    {
        if (! $this->supports($field->type())) {
            throw UnsupportedProjection::field('Filament', $field->name(), $field->type()->value);
        }

        if ($field->type() === FieldType::Checkbox || $field->type() === FieldType::Toggle) {
            return IconColumn::make($field->name())->boolean();
        }

        $column = TextColumn::make($field->name());

        if ($field->attributes()->label() !== null) {
            $column->label($field->attributes()->label());
        }

        return $column;
    }

    public function relationFormComponent(Relation $relation, ?string $titleAttribute): Select
    {
        if ($relation->type()->value !== 'belongsTo') {
            throw UnsupportedProjection::relation('Filament', $relation->name(), $relation->type()->value);
        }

        if ($titleAttribute === null || $titleAttribute === '') {
            throw new IncompleteProjection("Filament presentation for relation [{$relation->name()}] is missing titleAttribute.");
        }

        $foreignKey = $relation->keys()->foreignKey();

        if ($foreignKey === null || $foreignKey === '') {
            throw new IncompleteProjection("Relation [{$relation->name()}] is missing [foreignKey].");
        }

        $component = Select::make($foreignKey)
            ->relationship($relation->name(), $titleAttribute);

        if (! $relation->nullable()) {
            $component->required();
        }

        return $component;
    }

    private function makeFormComponent(Field $field): Component
    {
        $component = match ($field->type()) {
            FieldType::Text, FieldType::Title, FieldType::Slug, FieldType::Email, FieldType::Url, FieldType::Tel, FieldType::Password, FieldType::Id, FieldType::Uuid, FieldType::Ulid, FieldType::Integer, FieldType::BigInteger, FieldType::Decimal, FieldType::Float, FieldType::Money, FieldType::Percentage => TextInput::make($field->name()),
            FieldType::Textarea => Textarea::make($field->name()),
            FieldType::Checkbox => Checkbox::make($field->name()),
            FieldType::Toggle => Toggle::make($field->name()),
            FieldType::Date => DatePicker::make($field->name()),
            FieldType::DateTime => DateTimePicker::make($field->name())->timezone('UTC'),
            FieldType::Time => TimePicker::make($field->name()),
            FieldType::Select => Select::make($field->name()),
            FieldType::Radio => Radio::make($field->name()),
            default => throw UnsupportedProjection::field('Filament', $field->name(), $field->type()->value),
        };

        if ($component instanceof TextInput) {
            $this->configureTextInput($field, $component);
        }

        if ($component instanceof Select || $component instanceof Radio) {
            $this->configureChoice($field, $component);
        }

        return $component;
    }

    private function configureTextInput(Field $field, TextInput $component): void
    {
        $storage = Storage::forField($field);

        if ($storage->kind() === StorageKind::String && $storage->length() !== null) {
            $component->maxLength($storage->length());
        }

        match ($field->type()) {
            FieldType::Email => $component->email(),
            FieldType::Url => $component->url(),
            FieldType::Tel => $component->tel(),
            FieldType::Password => $component->password(),
            FieldType::Integer, FieldType::BigInteger => $component->integer(),
            FieldType::Decimal, FieldType::Float, FieldType::Money, FieldType::Percentage => $component->numeric(),
            default => null,
        };
    }

    private function configureChoice(Field $field, Select|Radio $component): void
    {
        $specification = $field->specification();

        if (! $specification instanceof ChoiceSpecification || $specification->options() === null) {
            throw UnsupportedProjection::field('Filament', $field->name(), $field->type()->value);
        }

        $options = $specification->options();

        if ($options->source() === OptionsSource::Static) {
            $component->options($options->values() ?? []);

            return;
        }

        if ($options->source() === OptionsSource::Enum && is_string($options->reference()) && enum_exists($options->reference())) {
            $enum = $options->reference();
            $choices = [];

            foreach ($enum::cases() as $case) {
                if (! $case instanceof BackedEnum) {
                    throw UnsupportedProjection::field('Filament', $field->name(), $field->type()->value);
                }

                $choices[(string) $case->value] = $case->name;
            }

            $component->options($choices);

            return;
        }

        throw new IncompleteProjection("Field [{$field->name()}] options source [{$options->source()->value}] is not bound for the Filament projection.");
    }

    private function applyField(Field $field, Component $component): void
    {
        $attributes = $field->attributes();

        if ($attributes->label() !== null && method_exists($component, 'label')) {
            $component->label($attributes->label());
        }

        if ($attributes->description() !== null && method_exists($component, 'helperText')) {
            $component->helperText($attributes->description());
        }

        if ($attributes->required() && method_exists($component, 'required')) {
            $component->required();
        }

        if ($attributes->hasDefault()) {
            $component->default($attributes->default());
        } else {
            $this->clearComponentDefault($component);
        }

        if ($attributes->systemManaged()) {
            $component->disabled();
            $component->dehydrated(false);
        }

        if ($attributes->immutable() && method_exists($component, 'readOnly')) {
            $component->readOnly();
        }
    }

    private function clearComponentDefault(Component $component): void
    {
        $flag = new ReflectionProperty($component, 'hasDefaultState');
        $flag->setValue($component, false);
        $state = new ReflectionProperty($component, 'defaultState');
        $state->setValue($component, null);
    }

    private function supports(FieldType $type): bool
    {
        return match ($type) {
            FieldType::Text, FieldType::Title, FieldType::Slug, FieldType::Email, FieldType::Url, FieldType::Tel, FieldType::Password, FieldType::Id, FieldType::Uuid, FieldType::Ulid, FieldType::Integer, FieldType::BigInteger, FieldType::Decimal, FieldType::Float, FieldType::Money, FieldType::Percentage, FieldType::Textarea, FieldType::Checkbox, FieldType::Toggle, FieldType::Date, FieldType::DateTime, FieldType::Time, FieldType::Select, FieldType::Radio => true,
            default => false,
        };
    }
}
