<?php

declare(strict_types=1);

use Moox\Support\Tests\Fixtures\IdentifierModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);


it('generates a ulid and uuid when creating a model', function () {
    $model = IdentifierModel::create();

    expect($model->ulid)->not->toBeEmpty()
        ->and($model->uuid)->not->toBeEmpty()
        ->and($model->hasUlid())->toBeTrue()
        ->and($model->hasUuid())->toBeTrue();
});

it('preserves provided identifiers when creating a model', function () {
    $ulid = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    $uuid = '550e8400-e29b-41d4-a716-446655440000';

    $model = IdentifierModel::create([
        'ulid' => $ulid,
        'uuid' => $uuid,
    ]);

    expect($model->ulid)->toBe($ulid)
        ->and($model->uuid)->toBe($uuid);
});

it('does not change identifiers when updating a model', function () {
    $model = IdentifierModel::create();

    $ulid = $model->ulid;
    $uuid = $model->uuid;

    $model->update([
        'ulid' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        'uuid' => '550e8400-e29b-41d4-a716-446655440000',
    ]);

    $model->refresh();

    expect($model->ulid)->toBe($ulid)
        ->and($model->uuid)->toBe($uuid);
});

it('detects whether a model has identifiers', function () {
    $model = new IdentifierModel;

    expect($model->hasUlid())->toBeFalse()
        ->and($model->hasUuid())->toBeFalse();

    $model->ulid = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    $model->uuid = '550e8400-e29b-41d4-a716-446655440000';

    expect($model->hasUlid())->toBeTrue()
        ->and($model->hasUuid())->toBeTrue();
});

it('checks whether a value is one of the model identifiers', function () {
    $model = IdentifierModel::create();

    expect($model->isIdentifier($model->ulid))->toBeTrue()
        ->and($model->isIdentifier($model->uuid))->toBeTrue()
        ->and($model->isIdentifier('unknown'))->toBeFalse();
});

it('finds a model by ulid or uuid', function () {
    $model = IdentifierModel::create();

    expect(IdentifierModel::query()->withIdentifier($model->ulid)->first()->is($model))
        ->toBeTrue()
        ->and(IdentifierModel::query()->withIdentifier($model->uuid)->first()->is($model))
        ->toBeTrue();
});

it('returns no model for an unknown identifier', function () {
    expect(IdentifierModel::query()->withIdentifier('unknown')->first())
        ->toBeNull();
});