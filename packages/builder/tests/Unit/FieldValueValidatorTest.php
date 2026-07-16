<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Illuminate\Validation\ValidationException;
use Moox\Builder\Compiler\SchemaCompiler;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Services\FieldValueValidator;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('rejects empty repeater rows', function (): void {
    $field = new FieldDefinition(
        name: 'ausstattung',
        label: 'Ausstattung',
        type: 'repeater',
        children: collect([
            new FieldDefinition(name: 'merkmal', label: 'Merkmal', type: 'text'),
            new FieldDefinition(name: 'enthalten', label: 'Enthalten', type: 'toggle'),
        ]),
    );

    $validator = app(FieldValueValidator::class);

    expect(fn () => $validator->assertValid($field, [
        ['merkmal' => '', 'enthalten' => false],
    ]))->toThrow(ValidationException::class);
});

it('accepts filled repeater rows', function (): void {
    $field = new FieldDefinition(
        name: 'ausstattung',
        label: 'Ausstattung',
        type: 'repeater',
        children: collect([
            new FieldDefinition(name: 'merkmal', label: 'Merkmal', type: 'text'),
            new FieldDefinition(name: 'enthalten', label: 'Enthalten', type: 'toggle'),
        ]),
    );

    app(FieldValueValidator::class)->assertValid($field, [
        ['merkmal' => 'Sitzheizung', 'enthalten' => true],
    ]);

    expect(true)->toBeTrue();
});

it('validates required nested fields inside repeaters', function (): void {
    $field = new FieldDefinition(
        name: 'ausstattung',
        label: 'Ausstattung',
        type: 'repeater',
        children: collect([
            new FieldDefinition(
                name: 'merkmal',
                label: 'Merkmal',
                type: 'text',
                validation: ['required' => true, 'rules' => []],
            ),
        ]),
    );

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [
        ['merkmal' => ''],
    ]))->toThrow(ValidationException::class);
});

it('builds nested validation rules for repeater children', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Vehicle data',
        'slug' => 'vehicle-data',
        'location_rules' => [],
        'active' => true,
    ]);

    $repeater = $group->fields()->create([
        'name' => 'ausstattung',
        'label' => 'Ausstattung',
        'type' => 'repeater',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $repeater->children()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'merkmal',
        'label' => 'Merkmal',
        'type' => 'text',
        'sort' => 0,
        'validation' => ['required' => true, 'rules' => []],
    ]);

    $group->load(['fields.children']);
    $definition = FieldGroupDefinition::fromModel($group);

    $rules = app(SchemaCompiler::class)->rules(collect([$definition]));

    expect($rules)->toHaveKey('ausstattung.*.merkmal')
        ->and($rules['ausstattung.*.merkmal'])->toContain('required');
});
