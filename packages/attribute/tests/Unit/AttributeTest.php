<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Moox\Attribute\Models\Attribute;

uses(RefreshDatabase::class);

it('attributes table migration: creates the correct columns', function () {
    expect(Schema::hasTable('attributes'))->toBeTrue();

    $columns = [
        'id', 'type', 'uuid', 'ulid', 'deleted_at',
    ];

    foreach ($columns as $column) {
        expect(Schema::hasColumn('attributes', $column))->toBeTrue();
    }
});

it('can create an attribute with translation', function () {
    $attribute = Attribute::create([
        'type' => 'test-type',
        'name' => 'test-name',
        'description' => 'test-description',
        'status' => 'draft',
        'value' => ['Durchmesser'],
    ]);
    expect($attribute)->toBeInstanceOf(Attribute::class);
    expect($attribute->name)->toBe('test-name');
    expect($attribute->description)->toBe('test-description');
    expect($attribute->value)->toBeArray();
    expect($attribute->value)->toBe(['Durchmesser']);
    expect($attribute->translation_status)->toBe('draft');
    expect($attribute->status)->toBe('draft');
});
