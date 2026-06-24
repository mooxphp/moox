<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';

use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Types\LinkFieldType;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldValueValidator;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();
});

it('normalizes link values and treats blank rows as null', function (): void {
    $type = new LinkFieldType;

    expect($type->castValue(null))->toBeNull()
        ->and($type->castValue(['url' => '', 'label' => '', 'opens_in_new_tab' => false]))->toBeNull()
        ->and($type->castValue([
            'url' => 'https://moox.org',
            'label' => 'Moox',
            'target' => '_blank',
        ]))->toBe([
            'url' => 'https://moox.org',
            'label' => 'Moox',
            'opens_in_new_tab' => true,
        ]);
});

it('persists and loads link values as json', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);
    $field = new FieldDefinition(name: 'cta', label: 'CTA', type: 'link');

    app(CustomFieldsManager::class)->saveValues('item', $record, [
        'cta' => [
            'url' => 'https://moox.org/items/demo',
            'label' => 'Zum Inserat',
            'opens_in_new_tab' => true,
        ],
    ], collect([$field]));

    $stored = FieldValue::query()->forRecord('item', $record->getKey())->first();

    expect($stored?->value_json)->toMatchArray([
        'url' => 'https://moox.org/items/demo',
        'label' => 'Zum Inserat',
        'opens_in_new_tab' => true,
    ]);

    $loaded = app(CustomFieldsManager::class)->loadValues('item', $record, collect([$field]));

    expect($loaded['cta'])->toMatchArray([
        'url' => 'https://moox.org/items/demo',
        'label' => 'Zum Inserat',
        'opens_in_new_tab' => true,
    ]);
});

it('rejects required links without a url', function (): void {
    $field = new FieldDefinition(
        name: 'cta',
        label: 'CTA',
        type: 'link',
        validation: ['required' => true, 'rules' => []],
    );

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [
        'url' => '',
        'label' => 'Nur Label',
        'opens_in_new_tab' => false,
    ]))->toThrow(ValidationException::class);
});

it('rejects invalid link urls on save', function (): void {
    $field = new FieldDefinition(name: 'cta', label: 'CTA', type: 'link');

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [
        'url' => 'not-a-valid-url',
        'label' => 'Broken',
        'opens_in_new_tab' => false,
    ]))->toThrow(ValidationException::class);
});

it('builds link fields with helper text configured on the url input', function (): void {
    $field = new FieldDefinition(
        name: 'cta',
        label: 'CTA',
        type: 'link',
        config: ['helperText' => 'Link zum externen Inserat.'],
    );

    $component = (new LinkFieldType)->formComponent($field);

    expect($component->getDefaultChildComponents())->toHaveCount(3)
        ->and($component->getDefaultChildComponents()[0])->toBeInstanceOf(\Filament\Forms\Components\TextInput::class);
});
