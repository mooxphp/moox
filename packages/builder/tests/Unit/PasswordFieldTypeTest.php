<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Types\PasswordFieldType;
use Moox\Builder\FieldTypes\Value\StoredPassword;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldValueValidator;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Secrets',
        'slug' => 'secrets',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'secret',
        'label' => 'Secret',
        'type' => 'password',
        'sort' => 0,
        'validation' => ['required' => true, 'rules' => []],
    ]);
});

it('validates required passwords when empty', function (): void {
    $field = new FieldDefinition(
        name: 'secret',
        label: 'Secret',
        type: 'password',
        validation: ['required' => true, 'rules' => []],
    );

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, ''))
        ->toThrow(ValidationException::class);
});

it('rejects missing required passwords when saving form data', function (): void {
    $record = TestItem::query()->create(['title' => 'New']);

    expect(fn () => app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        ['secret' => ''],
    ))->toThrow(ValidationException::class);
});

it('persists password values hashed and never reloads plaintext', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);
    $manager = app(CustomFieldsManager::class);

    $manager->saveFromFormData(TestItemResource::class, $record, [
        'secret' => 'api-key-456',
    ]);

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'secret')
        ->first();

    expect(Hash::check('api-key-456', (string) $stored?->value_string))->toBeTrue()
        ->and($manager->loadFormData(TestItemResource::class, $record))->toBe([
            'secret' => StoredPassword::instance(),
        ]);
});

it('clears password values when an empty string is saved', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Optional secrets',
        'slug' => 'optional-secrets',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'optional_secret',
        'label' => 'Optional Secret',
        'type' => 'password',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $record = TestItem::query()->create(['title' => 'Demo']);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $record->getKey(),
        'field_name' => 'optional_secret',
        'value_string' => 'api-key-456',
    ]);

    app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        ['optional_secret' => ''],
    );

    $stored = FieldValue::query()->forRecord('item', $record->getKey())
        ->where('field_name', 'optional_secret')
        ->first();

    expect($stored?->value_string)->toBeNull();
});

it('hashes nested password values inside groups before storage', function (): void {
    $field = new FieldDefinition(
        name: 'contact',
        label: 'Contact',
        type: 'group',
        children: collect([
            new FieldDefinition(name: 'pin', label: 'PIN', type: 'password'),
        ]),
    );

    $record = TestItem::query()->create(['title' => 'Demo']);

    app(CustomFieldsManager::class)->saveValues('item', $record, [
        'contact' => ['pin' => '1234'],
    ], collect([$field]));

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'contact')
        ->first();

    $pin = $stored?->value_json['pin'] ?? null;

    expect($pin)->not->toBe('1234')
        ->and(Hash::check('1234', (string) $pin))->toBeTrue();
});

it('builds revealable password inputs', function (): void {
    $component = (new PasswordFieldType)->formComponent(
        new FieldDefinition(
            name: 'secret',
            label: 'Secret',
            type: 'password',
        ),
    );

    expect($component)->toBeInstanceOf(TextInput::class)
        ->and($component->isPassword())->toBeTrue()
        ->and($component->isPasswordRevealable())->toBeTrue();
});
