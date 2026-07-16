<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Support\RelationValueRules;

class RelationSettings extends Capability
{
    /**
     * @return list<Component>
     */
    public function builderFieldsFor(string $fieldType): array
    {
        if ($fieldType !== 'relation') {
            return [];
        }

        return $this->builderFields();
    }

    /**
     * @return list<Component>
     */
    public function builderFields(): array
    {
        return [
            // Searchable requires live(): inside this reactive settings closure
            // a searchable select defers its state sync, so without live() the
            // selected value is dropped when another live field (e.g. multiple)
            // rebuilds the schema. live() commits the value immediately on
            // selection so it survives any subsequent rebuild.
            Select::make('config.related_entity')
                ->label(__('builder::builder.field.relation_entity'))
                ->helperText(fn (): string => app(EntityRegistry::class)->relatableOptions() === []
                    ? __('builder::builder.field_group.no_entities_registered')
                    : __('builder::builder.field.relation_entity_helper'))
                ->options(fn (): array => app(EntityRegistry::class)->relatableOptions())
                ->required()
                ->searchable()
                ->live()
                ->native(false)
                ->disabled(fn (): bool => app(EntityRegistry::class)->relatableOptions() === []),
            Toggle::make('config.multiple')
                ->label(__('builder::builder.field.relation_multiple'))
                ->helperText(__('builder::builder.field.relation_multiple_helper'))
                ->inline(false)
                ->live(),
            TextInput::make('config.min')
                ->label(__('builder::builder.field.relation_min'))
                ->numeric()
                ->minValue(0)
                ->visible(fn (callable $get): bool => (bool) $get('config.multiple')),
            TextInput::make('config.max')
                ->label(__('builder::builder.field.relation_max'))
                ->numeric()
                ->minValue(1)
                ->visible(fn (callable $get): bool => (bool) $get('config.multiple')),
        ];
    }

    /**
     * @return list<string|\Closure>
     */
    public function rules(FieldDefinition $field): array
    {
        return RelationValueRules::rules($field);
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        return $component;
    }
}
