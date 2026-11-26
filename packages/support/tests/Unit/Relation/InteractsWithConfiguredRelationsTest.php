<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Moox\Support\Tests\Fixtures\MorphOwnerModel;
use Moox\Support\Tests\Fixtures\RelationTestResource;
use Moox\Support\Tests\Fixtures\RelatedModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

beforeEach(function (): void {

    config([
        'relation.relations' => [],
        'relation.owner_types' => [],
    ]);
});

it('returns only configured belongs to relations', function (): void {
    config([
        'relation.relations' => [
            'relatedModel' => [
                'kind' => 'belongs_to',
                'foreign_key' => 'related_model_id',
            ],
            'owner' => [
                'kind' => 'morph_to',
                'morph_name' => 'owner',
            ],
            'invalid' => 'invalid',
            'hasMany' => [
                'kind' => 'has_many',
            ],
        ],
    ]);

    expect(RelationTestResource::belongsToRelationsForTest())
        ->toHaveCount(1)
        ->toHaveKey('relatedModel');
});

it('recognizes a listed belongs to relation', function (): void {
    expect(RelationTestResource::isListedBelongsToForTest(
        'relatedModel',
        ['kind' => 'belongs_to'],
    ))->toBeTrue();

    expect(RelationTestResource::isListedBelongsToForTest(
        'relatedModel',
        ['kind' => 'has_many'],
    ))->toBeFalse();

    expect(RelationTestResource::isListedBelongsToForTest(
        '',
        ['kind' => 'belongs_to'],
    ))->toBeFalse();

    expect(RelationTestResource::isListedBelongsToForTest(
        'relatedModel',
        'invalid',
    ))->toBeFalse();
});

it('recognizes morph relations', function (): void {
    expect(RelationTestResource::isMorphRelationForTest([
        'morph_name' => 'owner',
    ]))->toBeTrue();

    expect(RelationTestResource::isMorphRelationForTest([
        'morph_name' => '',
    ]))->toBeFalse();

    expect(RelationTestResource::isMorphRelationForTest([]))
        ->toBeFalse();
});

it('returns configured owner title columns', function (): void {
    expect(RelationTestResource::ownerTitleColumnsForTest([
        'title_attributes' => ['name', 'email'],
        'title_attribute' => 'title',
    ]))->toBe(['name', 'email']);

    expect(RelationTestResource::ownerTitleColumnsForTest([
        'title_attribute' => 'name',
    ]))->toBe(['name']);

    expect(RelationTestResource::ownerTitleColumnsForTest([
        'title_attributes' => ['name', '', null, 'email'],
    ]))->toBe(['name', 'email']);

    expect(RelationTestResource::ownerTitleColumnsForTest([]))
        ->toBe([]);
});

it('resolves a valid morph owner key', function (): void {
    config([
        'relation.owner_types' => [
            MorphOwnerModel::class => [
                'title_attribute' => 'name',
            ],
        ],
    ]);

    expect(RelationTestResource::morphOwnerFromKeyForTest(
        MorphOwnerModel::class.'|42',
    ))->toBe([
        'type' => MorphOwnerModel::class,
        'id' => '42',
    ]);
});

it('rejects invalid morph owner keys', function (): void {
    config([
        'relation.owner_types' => [
            MorphOwnerModel::class => [
                'title_attribute' => 'name',
            ],
        ],
    ]);

    expect(RelationTestResource::morphOwnerFromKeyForTest(null))
        ->toBeNull();

    expect(RelationTestResource::morphOwnerFromKeyForTest(''))
        ->toBeNull();

    expect(RelationTestResource::morphOwnerFromKeyForTest('invalid'))
        ->toBeNull();

    expect(RelationTestResource::morphOwnerFromKeyForTest('|42'))
        ->toBeNull();

    expect(RelationTestResource::morphOwnerFromKeyForTest(
        'UnknownModel|42',
    ))->toBeNull();

    expect(RelationTestResource::morphOwnerFromKeyForTest(
        MorphOwnerModel::class.'|',
    ))->toBeNull();
});

it('returns morph owner options matching the search term', function (): void {
    config([
        'relation.owner_types' => [
            MorphOwnerModel::class => [
                'title_attribute' => 'name',
            ],
        ],
    ]);

    $matchingOwner = MorphOwnerModel::query()->create([
        'name' => 'Example Owner',
    ]);

    MorphOwnerModel::query()->create([
        'name' => 'Another Record',
    ]);

    expect(RelationTestResource::morphOwnerOptionsForTest('Example'))
        ->toBe([
            MorphOwnerModel::class.'|'.$matchingOwner->getKey()
                => 'Example Owner',
        ]);
});

it('returns no morph owner options for an empty search', function (): void {
    config([
        'relation.owner_types' => [
            MorphOwnerModel::class => [
                'title_attribute' => 'name',
            ],
        ],
    ]);

    MorphOwnerModel::query()->create([
        'name' => 'Example Owner',
    ]);

    expect(RelationTestResource::morphOwnerOptionsForTest(''))
        ->toBe([]);
});

it('returns a morph owner label for a valid key', function (): void {
    config([
        'relation.owner_types' => [
            MorphOwnerModel::class => [
                'title_attribute' => 'name',
            ],
        ],
    ]);

    $owner = MorphOwnerModel::query()->create([
        'name' => 'Example Owner',
    ]);

    expect(RelationTestResource::morphOwnerLabelForTest(
        MorphOwnerModel::class.'|'.$owner->getKey(),
    ))->toBe('Example Owner');
});

it('returns null for an unknown morph owner label', function (): void {
    config([
        'relation.owner_types' => [
            MorphOwnerModel::class => [
                'title_attribute' => 'name',
            ],
        ],
    ]);

    expect(RelationTestResource::morphOwnerLabelForTest(
        MorphOwnerModel::class.'|999',
    ))->toBeNull();
});

it('uses the owner key as fallback label', function (): void {
    $owner = new RelatedModel();

    $owner->setAttribute('id', 123);

    expect(RelationTestResource::ownerRecordLabelForTest($owner))
        ->toBe('123');
});

it('returns the resource name from the model', function (): void {
    expect(RelationTestResource::featureResourceNameForTest())
        ->toBe('relation');
});