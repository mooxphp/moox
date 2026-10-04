<?php

declare(strict_types=1);

namespace Moox\Definition\Tests\Unit;

use Moox\Definition\Definitions\Capability;
use Moox\Definition\Definitions\EntityReference;
use Moox\Definition\Definitions\Field;
use Moox\Definition\Definitions\FieldAttributes;
use Moox\Definition\Definitions\FieldType;
use Moox\Definition\Definitions\Specifications\NoSpecification;
use Moox\Definition\Definitions\Specifications\TextSpecification;
use Moox\Definition\Projection\Storage;
use Moox\Definition\Projection\StorageKind;
use Moox\Definition\Projection\UnsupportedProjection;
use Moox\Definition\Validation\DefinitionValidator;

it('rejects a required nullable field', function (): void {
    $issues = (new DefinitionValidator)->validateField(
        Field::text('article_number', 32)->required()->nullable(),
        'fields.article_number',
    );

    expect(implode(' ', array_map(fn ($issue) => $issue->message(), $issues)))
        ->toContain('required field cannot be nullable');
});

it('rejects a field that has neither presence, nullability, default, nor system ownership', function (): void {
    $issues = (new DefinitionValidator)->validateField(Field::textarea('notes'), 'fields.notes');

    expect($issues)->not->toBeEmpty()
        ->and(implode(' ', array_map(fn ($issue) => $issue->message(), $issues)))
        ->toContain('default, systemManaged, or generated');
});

it('distinguishes an absent default from an explicit null default', function (): void {
    $absent = Field::textarea('notes')->nullable();
    $explicit = Field::textarea('notes')->nullable()->default(null);

    expect($absent->attributes()->hasDefault())->toBeFalse()
        ->and($explicit->attributes()->hasDefault())->toBeTrue()
        ->and($explicit->attributes()->default())->toBeNull();
});

it('rejects a null default when the field is not nullable', function (): void {
    $issues = (new DefinitionValidator)->validateField(
        Field::text('article_number', 32)->default(null),
        'fields.article_number',
    );

    expect(implode(' ', array_map(fn ($issue) => $issue->message(), $issues)))->toContain('null default');
});

it('rejects a closure default', function (): void {
    $issues = (new DefinitionValidator)->validateField(
        Field::text('article_number', 32)->required()->default(static fn (): string => 'A'),
        'fields.article_number',
    );

    expect(implode(' ', array_map(fn ($issue) => $issue->message(), $issues)))->toContain('serializable');
});

it('requires the established type semantics instead of inventing them at runtime', function (): void {
    $validator = new DefinitionValidator;

    expect(implode(' ', array_map(fn ($issue) => $issue->message(), $validator->validateField(Field::uuid('uuid'), 'uuid'))))
        ->toContain('unique')
        ->toContain('immutable')
        ->and(implode(' ', array_map(fn ($issue) => $issue->message(), $validator->validateField(Field::title('title'), 'title'))))
        ->toContain('required')
        ->and(implode(' ', array_map(fn ($issue) => $issue->message(), $validator->validateField(Field::slug('slug'), 'slug'))))
        ->toContain('unique');
});

it('requires maxLength for bounded text', function (): void {
    $field = new Field('article_number', FieldType::Text, new TextSpecification(null), FieldAttributes::make()->withRequired(true));
    $issues = (new DefinitionValidator)->validateField($field, 'fields.article_number');

    expect(implode(' ', array_map(fn ($issue) => $issue->message(), $issues)))->toContain('maxLength');
});

it('rejects a relation declared as a field', function (): void {
    $field = new Field('product_group', FieldType::Relation, new NoSpecification, FieldAttributes::make()->withNullable(true));
    $issues = (new DefinitionValidator)->validateField($field, 'fields.product_group');

    expect(implode(' ', array_map(fn ($issue) => $issue->message(), $issues)))->toContain('addRelation()');
});

it('parses optional capability input without treating it as a different type', function (): void {
    $capability = Capability::named('media.upload', ['file' => 'file', 'collection' => 'string?'], 'media', ['invalid_file'], 'media.upload', true);

    expect($capability->input()[1]->type())->toBe('string')
        ->and($capability->input()[1]->required())->toBeFalse()
        ->and($capability->sideEffects())->toBeTrue();
});

it('stores dateTime as a UTC timestamp and does not treat a referenced ulid as an id', function (): void {
    $instant = Storage::forField(Field::dateTime('available_at')->nullable());
    $reference = Storage::forReference(
        Field::ulid('ulid')->unique()->immutable()->systemManaged()->generated('ulid'),
    );

    expect($instant->kind())->toBe(StorageKind::Timestamp)
        ->and($reference->kind())->toBe(StorageKind::Ulid);
});

it('reports image storage as unsupported', function (): void {
    $field = Field::image('photo', new EntityReference('moox/media', 'media', 'ulid'))->nullable();

    expect(fn () => Storage::forField($field))->toThrow(UnsupportedProjection::class, 'image');
});
