<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/MediaTestHelpers.php';

use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Types\FileFieldType;
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

it('normalizes stored file snapshots', function (): void {
    $fieldType = new FileFieldType;

    $snapshot = [
        'id' => 42,
        'file_name' => 'brochure.pdf',
        'title' => 'Brochure',
        'alt' => null,
        'description' => null,
        'internal_note' => null,
    ];

    expect($fieldType->castValue($snapshot))->toBe($snapshot)
        ->and($fieldType->castValue(json_encode($snapshot)))->toBe($snapshot)
        ->and($fieldType->castValue(null))->toBeNull();
});

it('normalizes file values for the media picker form state', function (): void {
    $fieldType = new FileFieldType;

    expect($fieldType->normalizeForForm([
        'id' => 7,
        'file_name' => 'contract.pdf',
    ]))->toBe([7])
        ->and($fieldType->normalizeForForm([]))->toBe([]);
});

it('persists form state as a media snapshot', function (): void {
    MediaTestHelpers::seedMedia(99, 'existing.pdf', 'application/pdf');

    $fieldType = new FileFieldType;

    $snapshot = [
        'id' => 99,
        'file_name' => 'existing.pdf',
        'title' => null,
        'alt' => null,
        'description' => null,
        'internal_note' => null,
    ];

    expect($fieldType->persistValue($snapshot))->toBe($snapshot)
        ->and($fieldType->persistValue([99]))->toBe($snapshot)
        ->and($fieldType->persistValue([]))->toBeNull();
});

it('stores file values in value_json', function (): void {
    expect(TypedValueColumns::columnForType('file'))->toBe('value_json');
});

it('configures the file picker to exclude images', function (): void {
    $field = new FieldDefinition('attachment', 'Attachment', 'file');
    $component = (new FileFieldType)->formComponent($field);
    $config = $component->getUploadConfig();

    expect($config['excluded_mime_prefixes'] ?? null)->toBe(['image/'])
        ->and($config['accepted_file_types'] ?? [])->toContain('application/*');
});

it('saves file values through the custom fields manager', function (): void {
    MediaTestHelpers::seedMedia(3, 'download.pdf', 'application/pdf');

    $item = TestItem::query()->create(['title' => 'Post']);
    $field = new FieldDefinition('attachment', 'Attachment', 'file');

    app(CustomFieldsManager::class)->saveValues('item', $item, [
        'attachment' => [3],
    ], collect([$field]));

    $stored = FieldValue::query()
        ->forRecord('item', $item->getKey())
        ->where('field_name', 'attachment')
        ->first();

    expect($stored?->value_json)->toMatchArray([
        'id' => 3,
        'file_name' => 'download.pdf',
    ]);
});

it('rejects file values that reference missing media', function (): void {
    $field = new FieldDefinition('attachment', 'Attachment', 'file');

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [404]))
        ->toThrow(ValidationException::class);
});

it('rejects image media for file fields', function (): void {
    MediaTestHelpers::seedMedia(50, 'photo.jpg', 'image/jpeg');

    $field = new FieldDefinition('attachment', 'Attachment', 'file');
    $item = TestItem::query()->create(['title' => 'Post']);

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [50], $item))
        ->toThrow(ValidationException::class);
});

it('accepts non-image media for file fields', function (): void {
    MediaTestHelpers::seedMedia(51, 'notes.pdf', 'application/pdf');

    $field = new FieldDefinition('attachment', 'Attachment', 'file');
    $item = TestItem::query()->create(['title' => 'Post']);

    app(FieldValueValidator::class)->assertValid($field, [51], $item);

    expect(true)->toBeTrue();
});

it('rejects file values outside the record media scope', function (): void {
    $item = TestItem::query()->create(['title' => 'Post']);
    $item->setAttribute('scope', ScopeValue::forKeyString('item', ScopeValue::MODE_PRIVATE, 'item', '1'));

    $allowedScope = MediaFieldValueSupport::resolveExpectedMediaScope($item);

    MediaTestHelpers::seedMedia(60, 'allowed.pdf', 'application/pdf', $allowedScope);
    MediaTestHelpers::seedMedia(61, 'foreign.pdf', 'application/pdf', 'media:other:99:private');

    $field = new FieldDefinition('attachment', 'Attachment', 'file');

    app(FieldValueValidator::class)->assertValid($field, [60], $item);

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [61], $item))
        ->toThrow(ValidationException::class);
});

it('presents file values through the media item resource', function (): void {
    $field = new FieldDefinition('attachment', 'Attachment', 'file');
    $snapshot = [
        'id' => 12,
        'file_name' => 'manual.pdf',
        'title' => 'Manual',
        'alt' => null,
        'description' => null,
        'internal_note' => null,
    ];

    expect(app(BuilderValuesResolver::class)->presentFieldValue($field, $snapshot))
        ->toBe(MediaFieldValueSupport::presentSingle($snapshot));
});
