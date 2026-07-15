<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';

use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Services\BuilderValuesResolver;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldValueValidator;
use Moox\Builder\Support\ConditionalLogic;
use Moox\Builder\Support\TypedValueColumns;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();
});

function nestedGroupFieldDefinition(): FieldDefinition
{
    return FieldDefinition::fromArray([
        'name' => 'address',
        'label' => 'Address',
        'type' => 'group',
        'children' => [
            [
                'name' => 'has_company',
                'label' => 'Has company',
                'type' => 'toggle',
            ],
            [
                'name' => 'company_name',
                'label' => 'Company name',
                'type' => 'text',
                'validation' => ['required' => true, 'rules' => []],
                'settings' => [
                    'conditions' => [
                        'enabled' => true,
                        'action' => 'show',
                        'logic' => 'and',
                        'rules' => [
                            ['field' => 'has_company', 'operator' => 'equals', 'value' => '1'],
                        ],
                    ],
                ],
            ],
        ],
    ]);
}

it('evaluates nested conditional logic against sibling values in a group row', function (): void {
    $field = nestedGroupFieldDefinition();
    $company = $field->children->firstWhere('name', 'company_name');

    expect($company)->not->toBeNull()
        ->and(ConditionalLogic::isVisibleForValues($company, ['has_company' => true]))->toBeTrue()
        ->and(ConditionalLogic::isVisibleForValues($company, ['has_company' => false]))->toBeFalse();
});

it('skips validation for hidden nested fields', function (): void {
    $field = nestedGroupFieldDefinition();

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [
        'has_company' => false,
        'company_name' => '',
    ]))->not->toThrow(Exception::class);

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [
        'has_company' => true,
        'company_name' => '',
    ]))->toThrow(Illuminate\Validation\ValidationException::class);
});

it('clears hidden nested values on persist', function (): void {
    $field = nestedGroupFieldDefinition();

    $persisted = app(BuilderValuesResolver::class)->persistFieldValue($field, [
        'has_company' => false,
        'company_name' => 'injected',
    ]);

    expect($persisted)->toMatchArray([
        'has_company' => false,
        'company_name' => null,
    ]);
});

it('persists nested conditional logic through custom fields manager', function (): void {
    $record = TestItem::query()->create(['title' => 'Demo']);
    $field = nestedGroupFieldDefinition();
    $manager = app(CustomFieldsManager::class);

    $manager->saveValues('item', $record, [
        'address' => [
            'has_company' => false,
            'company_name' => 'injected',
        ],
    ], collect([$field]));

    $row = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'address')
        ->first();

    expect($row)->not->toBeNull();

    $stored = $row->value_json;

    expect($stored['has_company'])->toBeFalse()
        ->and($stored['company_name'])->toBeNull();
});

it('lists nested sibling fields as conditional-logic options', function (): void {
    require_once __DIR__.'/../Unit/ConditionalLogicFieldOptionsTest.php';

    $state = [
        'fields' => [
            'group' => [
                'name' => 'address',
                'type' => 'group',
                'label' => 'Address',
                'children' => [
                    'a' => [
                        'name' => 'has_company',
                        'type' => 'toggle',
                        'label' => 'Has company',
                    ],
                    'b' => [
                        'name' => 'company_name',
                        'type' => 'text',
                        'label' => 'Company name',
                        'settings' => ['conditions' => ['rules' => ['r1' => ['field' => null]]]],
                    ],
                ],
            ],
        ],
    ];

    $options = conditionalLogicFieldOptions($state, 'fields.group.children.b.settings.conditions.rules.r1');

    expect($options)->toBe(['has_company' => 'Has company']);
});
