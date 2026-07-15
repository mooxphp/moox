<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';

use Filament\Schemas\Components\Fieldset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Services\ClonedFieldGroupResolver;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Services\FieldGroupValidator;
use Moox\Builder\Services\FieldValueValidator;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();
});

function createSourceFieldGroup(string $slug = 'contact-details'): FieldGroup
{
    $group = FieldGroup::query()->create([
        'name' => 'Contact details',
        'slug' => $slug,
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Contact details',
        'slug' => $slug,
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ['name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'required' => false],
        ],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    return $group->fresh(['fields']);
}

it('registers clone as a compound field type', function (): void {
    $registry = app(FieldTypeRegistry::class);

    expect($registry->get('clone')->hasSubFields())->toBeTrue()
        ->and($registry->optionsForSelect())->toHaveKey('clone')
        ->and($registry->optionsForTabChildren())->toHaveKey('clone')
        ->and($registry->optionsForSubFields())->not->toHaveKey('clone');
});

it('resolves cloned children from the target field group slug', function (): void {
    createSourceFieldGroup();

    $cloneField = new FieldDefinition(
        name: 'contact',
        label: 'Contact',
        type: 'clone',
        config: ['field_group_slug' => 'contact-details'],
    );

    $children = app(ClonedFieldGroupResolver::class)->resolveChildren($cloneField);

    expect($children)->toHaveCount(2)
        ->and($children->pluck('name')->all())->toBe(['email', 'phone']);
});

it('compiles clone fields with subfields from the referenced group', function (): void {
    createSourceFieldGroup();

    $cloneField = new FieldDefinition(
        name: 'contact',
        label: 'Contact',
        type: 'clone',
        config: ['field_group_slug' => 'contact-details'],
    );

    $component = app(FieldTypeRegistry::class)
        ->get('clone')
        ->formComponent($cloneField);

    expect($component)->toBeInstanceOf(Fieldset::class)
        ->and($component->getDefaultChildComponents())->toHaveCount(2);
});

it('persists and loads clone values as nested json', function (): void {
    createSourceFieldGroup();

    $consumer = FieldGroup::query()->create([
        'name' => 'Consumer',
        'slug' => 'consumer',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($consumer, [
        'name' => 'Consumer',
        'slug' => 'consumer',
        'active' => true,
        'sort' => 1,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'contact',
                'label' => 'Contact',
                'type' => 'clone',
                'required' => false,
                'config' => ['field_group_slug' => 'contact-details'],
            ],
        ],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $record = TestItem::query()->create(['title' => 'Demo']);
    $manager = app(CustomFieldsManager::class);
    $fields = app(DefinitionRegistry::class)
        ->fieldGroupsFor(new LocationContext(entity: 'item'))
        ->flatMap(fn ($group) => $group->fields);

    $manager->saveValues('item', $record, [
        'contact' => [
            'email' => 'hello@example.com',
            'phone' => '123',
        ],
    ], $fields);

    expect($manager->loadValues('item', $record, $fields))->toMatchArray([
        'contact' => [
            'email' => 'hello@example.com',
            'phone' => '123',
        ],
    ]);
});

it('validates required fields inside a clone', function (): void {
    createSourceFieldGroup();

    $cloneField = new FieldDefinition(
        name: 'contact',
        label: 'Contact',
        type: 'clone',
        config: ['field_group_slug' => 'contact-details'],
    );

    expect(fn () => app(FieldValueValidator::class)->assertValid($cloneField, [
        'email' => '',
        'phone' => '',
    ]))->toThrow(ValidationException::class);

    expect(fn () => app(FieldValueValidator::class)->assertValid($cloneField, [
        'email' => 'hello@example.com',
        'phone' => '',
    ]))->not->toThrow(Exception::class);
});

it('rejects clone fields without a target field group', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Broken',
        'slug' => 'broken',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'slug' => 'broken',
        'fields' => [
            ['name' => 'contact', 'label' => 'Contact', 'type' => 'clone', 'config' => []],
        ],
        'target_entities' => ['item'],
        'location_constraints' => [],
    ]))->toThrow(ValidationException::class);
});

it('rejects cloning the same field group into itself', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Self',
        'slug' => 'self-group',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'slug' => 'self-group',
        'fields' => [
            [
                'name' => 'self',
                'label' => 'Self',
                'type' => 'clone',
                'config' => ['field_group_slug' => 'self-group'],
            ],
        ],
        'target_entities' => ['item'],
        'location_constraints' => [],
    ]))->toThrow(ValidationException::class);
});

it('does not persist subfields for clone fields', function (): void {
    createSourceFieldGroup();

    $consumer = FieldGroup::query()->create([
        'name' => 'Consumer',
        'slug' => 'consumer-sync',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($consumer, [
        'name' => 'Consumer',
        'slug' => 'consumer-sync',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'contact',
                'label' => 'Contact',
                'type' => 'clone',
                'required' => false,
                'config' => ['field_group_slug' => 'contact-details'],
                'children' => [
                    ['name' => 'ignored', 'label' => 'Ignored', 'type' => 'text', 'required' => false],
                ],
            ],
        ],
    ]);

    $clone = $consumer->fresh(['fields'])->fields->first();

    expect($clone)->not->toBeNull()
        ->and($clone->children)->toHaveCount(0)
        ->and($clone->config)->toMatchArray(['field_group_slug' => 'contact-details']);
});
