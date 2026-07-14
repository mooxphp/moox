<?php

declare(strict_types=1);

use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Support\FilterableFieldTypes;

it('supports choice fields with options', function (): void {
    $field = FieldDefinition::fromArray([
        'name' => 'fuel',
        'label' => 'Fuel',
        'type' => 'select',
        'options' => [
            ['label' => 'Petrol', 'value' => 'petrol'],
        ],
    ]);

    expect(FilterableFieldTypes::supports($field))->toBeTrue();
});

it('does not support choice fields without options', function (): void {
    $field = FieldDefinition::fromArray([
        'name' => 'fuel',
        'label' => 'Fuel',
        'type' => 'radio',
        'options' => [],
    ]);

    expect(FilterableFieldTypes::supports($field))->toBeFalse();
});

it('supports toggle fields', function (): void {
    $field = FieldDefinition::fromArray([
        'name' => 'active',
        'label' => 'Active',
        'type' => 'toggle',
    ]);

    expect(FilterableFieldTypes::supports($field))->toBeTrue();
});

it('supports single relation fields with a related entity', function (): void {
    $field = FieldDefinition::fromArray([
        'name' => 'linked_item',
        'label' => 'Linked item',
        'type' => 'relation',
        'config' => ['related_entity' => 'item', 'multiple' => false],
    ]);

    expect(FilterableFieldTypes::supports($field))->toBeTrue();
});

it('does not support multiple relation fields', function (): void {
    $field = FieldDefinition::fromArray([
        'name' => 'linked_items',
        'label' => 'Linked items',
        'type' => 'relation',
        'config' => ['related_entity' => 'item', 'multiple' => true],
    ]);

    expect(FilterableFieldTypes::supports($field))->toBeFalse();
});

it('does not support relation fields without a related entity', function (): void {
    $field = FieldDefinition::fromArray([
        'name' => 'linked_item',
        'label' => 'Linked item',
        'type' => 'relation',
        'config' => ['multiple' => false],
    ]);

    expect(FilterableFieldTypes::supports($field))->toBeFalse();
});

it('does not support non-filterable field types', function (): void {
    $field = FieldDefinition::fromArray([
        'name' => 'notes',
        'label' => 'Notes',
        'type' => 'text',
    ]);

    expect(FilterableFieldTypes::supports($field))->toBeFalse();
});
