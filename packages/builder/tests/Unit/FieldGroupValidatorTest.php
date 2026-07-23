<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Services\FieldGroupValidator;
use Moox\Builder\Support\StorableFieldCollector;
use Moox\Builder\Tests\TestCase;
use Spatie\Permission\Traits\HasRoles;

uses(TestCase::class);

it('collects flattened storable names from definitions', function (): void {
    $collector = app(StorableFieldCollector::class);

    $names = $collector->namesFromList(collect([
        new FieldDefinition(name: 'hinweis', label: 'Hinweis', type: 'message'),
        new FieldDefinition(
            name: 'tab-one',
            label: 'One',
            type: 'tab',
            children: collect([
                new FieldDefinition(name: 'email', label: 'Email', type: 'email'),
            ]),
        ),
        new FieldDefinition(
            name: 'standort',
            label: 'Standort',
            type: 'group',
            children: collect([
                new FieldDefinition(name: 'stadt', label: 'Stadt', type: 'text'),
            ]),
        ),
    ]));

    expect($names)->toBe(['email', 'standort']);
});

it('rejects duplicate storable names across tabs', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Tabs',
        'slug' => 'tabs-dup',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'tab-a',
                'label' => 'A',
                'type' => 'tab',
                'children' => [
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ],
            ],
            [
                'name' => 'tab-b',
                'label' => 'B',
                'type' => 'tab',
                'children' => [
                    ['name' => 'email', 'label' => 'Email 2', 'type' => 'email'],
                ],
            ],
        ],
    ]))->toThrow(ValidationException::class);
});

it('rejects duplicate storable names between root and tab children', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Mixed',
        'slug' => 'mixed-dup',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'target_entities' => ['item'],
        'fields' => [
            ['name' => 'preis', 'label' => 'Preis', 'type' => 'number'],
            [
                'name' => 'tab-a',
                'label' => 'A',
                'type' => 'tab',
                'children' => [
                    ['name' => 'preis', 'label' => 'Preis 2', 'type' => 'number'],
                ],
            ],
        ],
    ]))->toThrow(ValidationException::class);
});

it('allows nested subfield names inside compound fields', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Compound',
        'slug' => 'compound-ok',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupValidator::class)->validate($group, [
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'standort',
                'label' => 'Standort',
                'type' => 'group',
                'children' => [
                    ['name' => 'stadt', 'label' => 'Stadt', 'type' => 'text'],
                ],
            ],
            [
                'name' => 'kontakt',
                'label' => 'Kontakt',
                'type' => 'group',
                'children' => [
                    ['name' => 'stadt', 'label' => 'Stadt', 'type' => 'text'],
                ],
            ],
        ],
    ]);

    expect(true)->toBeTrue();
});

it('does not treat nested subfield names as external conflicts', function (): void {
    $existing = FieldGroup::query()->create([
        'name' => 'Existing',
        'slug' => 'existing-nested',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($existing, [
        'name' => 'Existing',
        'slug' => 'existing-nested',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'standort',
                'label' => 'Standort',
                'type' => 'group',
                'children' => [
                    ['name' => 'stadt', 'label' => 'Stadt', 'type' => 'text'],
                ],
            ],
        ],
    ]);

    $incoming = FieldGroup::query()->create([
        'name' => 'Incoming',
        'slug' => 'incoming-root',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupValidator::class)->validate($incoming, [
        'target_entities' => ['item'],
        'fields' => [
            ['name' => 'stadt', 'label' => 'Stadt', 'type' => 'text'],
        ],
    ]);

    expect(true)->toBeTrue();
});

it('detects external conflicts for flattened storable names across groups', function (): void {
    $existing = FieldGroup::query()->create([
        'name' => 'Existing',
        'slug' => 'existing-email',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($existing, [
        'name' => 'Existing',
        'slug' => 'existing-email',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'tab-a',
                'label' => 'A',
                'type' => 'tab',
                'children' => [
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ],
            ],
        ],
    ]);

    $incoming = FieldGroup::query()->create([
        'name' => 'Incoming',
        'slug' => 'incoming-email',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($incoming, [
        'target_entities' => ['item'],
        'fields' => [
            ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
        ],
    ]))->toThrow(function (ValidationException $exception): void {
        expect($exception->errors())
            ->toHaveKey('fields.0.name')
            ->toHaveKey('target_entities');
    });
});

it('rejects unknown location constraint params', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Rules',
        'slug' => 'rules-invalid-param',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'target_entities' => ['item'],
        'location_constraints' => [
            ['param' => 'template', 'operator' => '==', 'value' => 'landing'],
        ],
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
        ],
    ]))->toThrow(function (ValidationException $exception): void {
        expect($exception->errors())->toHaveKey('location_constraints.0.param');
    });
});

it('rejects unknown structured validation rules', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Rules',
        'slug' => 'rules-invalid-validation-rule',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'title',
                'label' => 'Title',
                'type' => 'text',
                'validation' => [
                    'rule_rows' => [
                        ['rule' => 'integer'],
                    ],
                ],
            ],
        ],
    ]))->toThrow(function (ValidationException $exception): void {
        expect($exception->errors())->toHaveKey('fields.0.validation.rule_rows.0.rule');
    });
});

it('rejects unknown raw validation rules', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Rules',
        'slug' => 'rules-invalid-raw-validation-rule',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'title',
                'label' => 'Title',
                'type' => 'text',
                'validation' => [
                    'raw_rules' => "starts_with:foo\nprohibited",
                ],
            ],
        ],
    ]))->toThrow(function (ValidationException $exception): void {
        expect($exception->errors())->toHaveKey('fields.0.validation.raw_rules');
    });
});

it('rejects missing values for structured validation rules', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Rules',
        'slug' => 'rules-missing-validation-value',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'title',
                'label' => 'Title',
                'type' => 'text',
                'validation' => [
                    'rule_rows' => [
                        ['rule' => 'starts_with', 'value' => ''],
                    ],
                ],
            ],
        ],
    ]))->toThrow(function (ValidationException $exception): void {
        expect($exception->errors())->toHaveKey('fields.0.validation.rule_rows.0.value');
    });
});

it('rejects non numeric values for numeric validation rules', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Rules',
        'slug' => 'rules-nonnumeric-validation-value',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'price',
                'label' => 'Price',
                'type' => 'number',
                'validation' => [
                    'rule_rows' => [
                        ['rule' => 'gte', 'value' => 'abc'],
                    ],
                ],
            ],
        ],
    ]))->toThrow(function (ValidationException $exception): void {
        expect($exception->errors())->toHaveKey('fields.0.validation.rule_rows.0.value');
    });
});

it('rejects record type constraints when selected entities do not support them', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Rules',
        'slug' => 'rules-invalid-record-type',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'target_entities' => ['item'],
        'location_constraints' => [
            ['param' => 'record_type', 'operator' => '==', 'value' => 'page'],
        ],
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
        ],
    ]))->toThrow(function (ValidationException $exception): void {
        expect($exception->errors())->toHaveKey('location_constraints.0.param');
    });
});

it('rejects user role constraints when roles are unavailable', function (): void {
    config()->set('permission.table_names.roles', 'roles');
    config()->set('auth.providers.users.model', ValidatorNoRolesUser::class);

    $group = FieldGroup::query()->create([
        'name' => 'Rules',
        'slug' => 'rules-invalid-role',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'target_entities' => ['item'],
        'location_constraints' => [
            ['param' => 'user_role', 'operator' => '==', 'value' => 'admin'],
        ],
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
        ],
    ]))->toThrow(function (ValidationException $exception): void {
        expect($exception->errors())->toHaveKey('location_constraints.0.value');
    });
});

it('rejects unknown user role values', function (): void {
    config()->set('permission.table_names.roles', 'roles');
    config()->set('auth.providers.users.model', ValidatorRolesUser::class);

    Schema::create('roles', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
    });

    DB::table('roles')->insert([
        'name' => 'editor',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $group = FieldGroup::query()->create([
        'name' => 'Rules',
        'slug' => 'rules-unknown-role',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    expect(fn () => app(FieldGroupValidator::class)->validate($group, [
        'target_entities' => ['item'],
        'location_constraints' => [
            ['param' => 'user_role', 'operator' => '==', 'value' => 'admin'],
        ],
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
        ],
    ]))->toThrow(function (ValidationException $exception): void {
        expect($exception->errors())->toHaveKey('location_constraints.0.value');
    });
});

class ValidatorNoRolesUser extends Model {}

class ValidatorRolesUser extends Model
{
    use HasRoles;
}
