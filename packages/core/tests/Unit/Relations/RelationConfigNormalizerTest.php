<?php

declare(strict_types=1);

use Moox\Core\Relations\Enums\RelationKind;
use Moox\Core\Relations\Enums\RelationPerspective;
use Moox\Core\Relations\Enums\RelationPresentation;
use Moox\Core\Relations\RelationConfigNormalizer;

it('normalizes legacy taxonomies as inline morph pivots', function (): void {
    $normalized = RelationConfigNormalizer::fromTaxonomies([
        'tags' => [
            'model' => 'Tag',
            'table' => 'taggables',
            'relationship' => 'taggable',
            'foreignKey' => 'taggable_id',
            'relatedKey' => 'tag_id',
        ],
    ]);

    expect($normalized['tags'])
        ->kind->toBe(RelationKind::MorphPivot->value)
        ->presentation->toBe(RelationPresentation::Inline->value)
        ->and($normalized['tags']['related_model'])->toBe('Tag');
});

it('normalizes morph relations as owner tab pivots', function (): void {
    $normalized = RelationConfigNormalizer::fromMorphRelations([
        'addresses' => [
            'model' => 'Address',
            'pivot_table' => 'addressables',
            'morph_name' => 'addressable',
        ],
    ]);

    expect($normalized['addresses'])
        ->kind->toBe(RelationKind::MorphPivot->value)
        ->perspective->toBe(RelationPerspective::Owner->value)
        ->presentation->toBe(RelationPresentation::Tab->value);
});

it('merges related morph defaults from package config', function (): void {
    $merged = RelationConfigNormalizer::normalize('addressables', [
        'model' => 'Moox\\Address\\Models\\Address',
        'pivot_table' => 'addressables',
    ]);

    expect($merged)
        ->toHaveKey('display_columns')
        ->and($merged['related_resource'] ?? null)->toBe('Moox\\Address\\Resources\\AddressResource');
})->skip(fn (): bool => ! config()->has('address.related_morph_defaults'), 'Address config not loaded');

it('infers belongs to many and pivot has many kinds', function (): void {
    $belongsToMany = RelationConfigNormalizer::normalize('companies', [
        'model' => 'Company',
        'pivot_table' => 'company_contact',
        'inverse_relationship' => 'contacts',
    ]);

    $pivotHasMany = RelationConfigNormalizer::normalize('addressables', [
        'pivot_model' => 'Addressable',
        'owner_types' => ['Company' => 'Company'],
    ]);

    expect($belongsToMany['kind'])->toBe(RelationKind::BelongsToMany->value)
        ->and($pivotHasMany['kind'])->toBe(RelationKind::PivotHasMany->value)
        ->and($pivotHasMany['perspective'])->toBe(RelationPerspective::Related->value);
});
