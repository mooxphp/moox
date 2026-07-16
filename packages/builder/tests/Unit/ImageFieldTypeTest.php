<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/MediaTestHelpers.php';

use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Types\ImageFieldType;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
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

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Media',
        'slug' => 'media',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'hero_image',
        'label' => 'Hero image',
        'type' => 'image',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);
});

it('normalizes stored image snapshots', function (): void {
    $fieldType = new ImageFieldType;

    $snapshot = [
        'id' => 42,
        'file_name' => 'hero.jpg',
        'title' => 'Hero',
        'alt' => 'Hero alt',
        'description' => null,
        'internal_note' => null,
    ];

    expect($fieldType->castValue($snapshot))->toBe($snapshot)
        ->and($fieldType->castValue(json_encode($snapshot)))->toBe($snapshot)
        ->and($fieldType->castValue(null))->toBeNull();
});

it('normalizes image values for the media picker form state', function (): void {
    $fieldType = new ImageFieldType;

    expect($fieldType->normalizeForForm([
        'id' => 7,
        'file_name' => 'cover.png',
    ]))->toBe([7])
        ->and($fieldType->normalizeForForm([]))->toBe([])
        ->and($fieldType->normalizeForForm(null))->toBe([]);
});

it('persists form state as a media snapshot', function (): void {
    $fieldType = new ImageFieldType;

    MediaTestHelpers::seedMedia(99, 'existing.jpg');

    $snapshot = [
        'id' => 99,
        'file_name' => 'existing.jpg',
        'title' => null,
        'alt' => null,
        'description' => null,
        'internal_note' => null,
    ];

    expect($fieldType->persistValue($snapshot))->toBe($snapshot)
        ->and($fieldType->persistValue([99]))->toBe($snapshot)
        ->and($fieldType->persistValue([]))->toBeNull();
});

it('stores image values in value_json', function (): void {
    expect(TypedValueColumns::columnForType('image'))->toBe('value_json');
});

it('configures the image picker to only show images', function (): void {
    $field = new FieldDefinition('hero_image', 'Hero image', 'image');
    $config = (new ImageFieldType)->formComponent($field)->getUploadConfig();

    expect($config['only_mime_prefixes'] ?? null)->toBe(['image/'])
        ->and($config['accepted_file_types'] ?? [])->toContain('image/*');
});

it('loads and presents image custom field values', function (): void {
    $item = TestItem::query()->create(['title' => 'Post']);

    $snapshot = [
        'id' => 12,
        'file_name' => 'banner.webp',
        'title' => 'Banner',
        'alt' => 'Banner alt',
        'description' => null,
        'internal_note' => null,
    ];

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $item->getKey(),
        'field_name' => 'hero_image',
        'value_json' => $snapshot,
    ]);

    $item->flushCustomFieldsCache();

    expect($item->customField('hero_image'))->toBe($snapshot);

    $field = new FieldDefinition('hero_image', 'Hero image', 'image');

    expect(app(BuilderValuesResolver::class)->presentFieldValue($field, $snapshot))
        ->toBe($snapshot);
});

it('extracts media ids from mixed state shapes', function (): void {
    expect(MediaFieldValueSupport::extractIds([42]))->toBe([42])
        ->and(MediaFieldValueSupport::extractIds(['id' => 5, 'file_name' => 'a.jpg']))->toBe([5])
        ->and(MediaFieldValueSupport::extractIds(null))->toBe([]);
});

it('saves image values through the custom fields manager', function (): void {
    MediaTestHelpers::seedMedia(3, 'thumb.jpg');

    $item = TestItem::query()->create(['title' => 'Post']);
    $field = new FieldDefinition('hero_image', 'Hero image', 'image');

    app(CustomFieldsManager::class)->saveValues('item', $item, [
        'hero_image' => [3],
    ], collect([$field]));

    $item->flushCustomFieldsCache();

    $stored = FieldValue::query()
        ->forRecord('item', $item->getKey())
        ->where('field_name', 'hero_image')
        ->first();

    expect($stored)->not->toBeNull()
        ->and($stored->value_json)->toMatchArray([
            'id' => 3,
            'file_name' => 'thumb.jpg',
        ])
        ->and($item->customField('hero_image'))->toBe($stored->value_json);
});

it('rejects image values that reference missing media', function (): void {
    $field = new FieldDefinition('hero_image', 'Hero image', 'image');

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [404]))
        ->toThrow(ValidationException::class);

    expect(fn () => app(CustomFieldsManager::class)->saveValues(
        'item',
        TestItem::query()->create(['title' => 'Post']),
        ['hero_image' => [404]],
        collect([$field]),
    ))->toThrow(ValidationException::class);
});

it('validates required image fields as empty when no media is selected', function (): void {
    $field = new FieldDefinition(
        name: 'hero_image',
        label: 'Hero image',
        type: 'image',
        validation: ['required' => true, 'rules' => []],
    );

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, []))
        ->toThrow(ValidationException::class);
});

it('rejects image values that are not images', function (): void {
    MediaTestHelpers::seedMedia(50, 'document.pdf', 'application/pdf');

    $field = new FieldDefinition('hero_image', 'Hero image', 'image');
    $item = TestItem::query()->create(['title' => 'Post']);

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [50], $item))
        ->toThrow(ValidationException::class);
});

it('rejects image values outside the record media scope', function (): void {
    $item = TestItem::query()->create(['title' => 'Post']);
    $item->setAttribute('scope', ScopeValue::forKeyString('item', ScopeValue::MODE_PRIVATE, 'item', '1'));

    $allowedScope = MediaFieldValueSupport::resolveExpectedMediaScope($item);

    MediaTestHelpers::seedMedia(60, 'allowed.jpg', 'image/jpeg', $allowedScope);
    MediaTestHelpers::seedMedia(61, 'foreign.jpg', 'image/jpeg', 'media:other:99:private');

    $field = new FieldDefinition('hero_image', 'Hero image', 'image');

    app(FieldValueValidator::class)->assertValid($field, [60], $item);

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, [61], $item))
        ->toThrow(ValidationException::class);
});
