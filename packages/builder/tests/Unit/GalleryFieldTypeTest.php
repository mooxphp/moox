<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/MediaTestHelpers.php';

use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Types\GalleryFieldType;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Services\BuilderValuesResolver;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldValueValidator;
use Moox\Builder\Support\MediaFieldValueSupport;
use Moox\Builder\Support\MediaIntegration;
use Moox\Builder\Support\TypedValueColumns;
use Moox\Builder\Tests\Support\MediaTestHelpers;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\TestCase;
use Moox\Core\Support\Scopes\ScopeValue;

uses(TestCase::class);

beforeEach(function (): void {
    if (! MediaIntegration::isAvailable()) {
        $this->markTestSkipped('moox/media is not installed.');
    }

    $this->createItemsTable();
    MediaTestHelpers::ensureMediaTableExists();
});

it('normalizes gallery values as indexed snapshots', function (): void {
    $fieldType = new GalleryFieldType;

    $gallery = [
        '1' => [
            'id' => 1,
            'file_name' => 'a.jpg',
            'title' => 'A',
            'alt' => null,
            'description' => null,
            'internal_note' => null,
        ],
        '2' => [
            'id' => 2,
            'file_name' => 'b.jpg',
            'title' => 'B',
            'alt' => null,
            'description' => null,
            'internal_note' => null,
        ],
    ];

    expect($fieldType->castValue($gallery))->toBe($gallery)
        ->and(MediaFieldValueSupport::isIndexedGallery($gallery))->toBeTrue();
});

it('normalizes gallery values for the media picker form state', function (): void {
    $fieldType = new GalleryFieldType;

    expect($fieldType->normalizeForForm([
        '1' => ['id' => 4, 'file_name' => 'a.jpg'],
        '2' => ['id' => 8, 'file_name' => 'b.jpg'],
    ]))->toBe([4, 8])
        ->and($fieldType->normalizeForForm([]))->toBe([]);
});

it('persists gallery form state as indexed snapshots', function (): void {
    MediaTestHelpers::seedMedia(4, 'a.jpg');
    MediaTestHelpers::seedMedia(8, 'b.jpg');

    $fieldType = new GalleryFieldType;

    expect($fieldType->persistValue([4, 8]))->toBe([
        '1' => [
            'id' => 4,
            'file_name' => 'a.jpg',
            'title' => null,
            'alt' => null,
            'description' => null,
            'internal_note' => null,
        ],
        '2' => [
            'id' => 8,
            'file_name' => 'b.jpg',
            'title' => null,
            'alt' => null,
            'description' => null,
            'internal_note' => null,
        ],
    ]);
});

it('stores gallery values in value_json', function (): void {
    expect(TypedValueColumns::columnForType('gallery'))->toBe('value_json');
});

it('configures the gallery picker to only show images', function (): void {
    $field = new FieldDefinition('photos', 'Photos', 'gallery');
    $config = (new GalleryFieldType)->formComponent($field)->getUploadConfig();

    expect($config['only_mime_prefixes'] ?? null)->toBe(['image/'])
        ->and($config['accepted_file_types'] ?? [])->toContain('image/*');
});

it('saves gallery values through the custom fields manager', function (): void {
    MediaTestHelpers::seedMedia(5, 'one.jpg');
    MediaTestHelpers::seedMedia(6, 'two.jpg');

    $item = TestItem::query()->create(['title' => 'Post']);
    $field = new FieldDefinition('photos', 'Photos', 'gallery');

    app(CustomFieldsManager::class)->saveValues('item', $item, [
        'photos' => [5, 6],
    ], collect([$field]));

    $stored = FieldValue::query()
        ->forRecord('item', $item->getKey())
        ->where('field_name', 'photos')
        ->first();

    expect($stored?->value_json)->toBe([
        '1' => [
            'id' => 5,
            'file_name' => 'one.jpg',
            'title' => null,
            'alt' => null,
            'description' => null,
            'internal_note' => null,
        ],
        '2' => [
            'id' => 6,
            'file_name' => 'two.jpg',
            'title' => null,
            'alt' => null,
            'description' => null,
            'internal_note' => null,
        ],
    ]);
});

it('rejects gallery values with missing media', function (): void {
    $field = new FieldDefinition('photos', 'Photos', 'gallery');

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [404]))
        ->toThrow(ValidationException::class);
});

it('validates gallery min and max file limits', function (): void {
    MediaTestHelpers::seedMedia(1, 'one.jpg');

    $field = new FieldDefinition(
        name: 'photos',
        label: 'Photos',
        type: 'gallery',
        config: ['min_files' => 2, 'max_files' => 3],
    );

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [1]))
        ->toThrow(ValidationException::class);

    MediaTestHelpers::seedMedia(2, 'two.jpg');
    MediaTestHelpers::seedMedia(3, 'three.jpg');
    MediaTestHelpers::seedMedia(4, 'four.jpg');

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [1, 2, 3, 4]))
        ->toThrow(ValidationException::class);

    expect(app(FieldValueValidator::class)->messagesFor(
        $field,
        [1, 2],
    ))->toBe([]);
});

it('presents gallery values with indexed output', function (): void {
    $field = new FieldDefinition('photos', 'Photos', 'gallery');
    $gallery = [
        '1' => [
            'id' => 12,
            'file_name' => 'banner.webp',
            'title' => 'Banner',
            'alt' => null,
            'description' => null,
            'internal_note' => null,
        ],
    ];

    expect(app(BuilderValuesResolver::class)->presentFieldValue($field, $gallery))
        ->toBe(MediaFieldValueSupport::presentGallery($gallery));
});

it('replaces snapshots inside gallery values during metadata sync', function (): void {
    $fresh = [
        'id' => 2,
        'file_name' => 'updated.jpg',
        'title' => 'Updated',
        'alt' => null,
        'description' => null,
        'internal_note' => null,
    ];

    $stored = [
        '1' => ['id' => 1, 'file_name' => 'one.jpg', 'title' => null, 'alt' => null, 'description' => null, 'internal_note' => null],
        '2' => ['id' => 2, 'file_name' => 'two.jpg', 'title' => 'Old', 'alt' => null, 'description' => null, 'internal_note' => null],
    ];

    expect(MediaFieldValueSupport::replaceSnapshotInStoredValue($stored, 2, $fresh))
        ->toBe([
            '1' => $stored['1'],
            '2' => $fresh,
        ]);
});

it('rejects gallery values that are not images', function (): void {
    MediaTestHelpers::seedMedia(70, 'document.pdf', 'application/pdf');

    $field = new FieldDefinition('photos', 'Photos', 'gallery');
    $item = TestItem::query()->create(['title' => 'Post']);

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [70], $item))
        ->toThrow(ValidationException::class);
});

it('rejects gallery values outside the record media scope', function (): void {
    $item = TestItem::query()->create(['title' => 'Post']);
    $item->setAttribute('scope', ScopeValue::forKeyString('item', ScopeValue::MODE_PRIVATE, 'item', '1'));

    $allowedScope = MediaFieldValueSupport::resolveExpectedMediaScope($item);

    MediaTestHelpers::seedMedia(80, 'allowed.jpg', 'image/jpeg', $allowedScope);
    MediaTestHelpers::seedMedia(81, 'foreign.jpg', 'image/jpeg', 'media:other:99:private');

    $field = new FieldDefinition('photos', 'Photos', 'gallery');

    app(FieldValueValidator::class)->assertValid($field, [80], $item);

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [81], $item))
        ->toThrow(ValidationException::class);
});
