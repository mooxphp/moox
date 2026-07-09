<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/MediaTestHelpers.php';

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\BuilderFieldValueMediaMetadataSync;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Support\MediaIntegration;
use Moox\Builder\Tests\Support\MediaTestHelpers;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! MediaIntegration::isAvailable()) {
        $this->markTestSkipped('moox/media is not installed.');
    }

    $this->createItemsTable();
    MediaTestHelpers::ensureMediaTableExists();

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
    ]);
});

it('updates builder field value snapshots when media metadata changes', function (): void {
    MediaTestHelpers::seedMedia(10, 'hero.jpg');

    $item = TestItem::query()->create(['title' => 'Post']);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $item->getKey(),
        'field_name' => 'hero_image',
        'value_json' => [
            'id' => 10,
            'file_name' => 'hero.jpg',
            'title' => 'Old title',
            'alt' => 'Old alt',
            'description' => null,
            'internal_note' => null,
        ],
    ]);

    DB::table('media')->where('id', 10)->update(['file_name' => 'hero-v2.jpg']);

    app(BuilderFieldValueMediaMetadataSync::class)->syncForMedia(10);

    expect(FieldValue::query()->first()?->value_json)->toMatchArray([
        'id' => 10,
        'file_name' => 'hero-v2.jpg',
    ]);
});

it('invalidates the custom fields manager cache after metadata sync', function (): void {
    MediaTestHelpers::seedMedia(11, 'cover.jpg');

    $item = TestItem::query()->create(['title' => 'Post']);
    $fields = collect([new FieldDefinition('hero_image', 'Hero image', 'image')]);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $item->getKey(),
        'field_name' => 'hero_image',
        'value_json' => [
            'id' => 11,
            'file_name' => 'cover.jpg',
            'title' => 'Old',
            'alt' => null,
            'description' => null,
            'internal_note' => null,
        ],
    ]);

    app(CustomFieldsManager::class)->loadCachedValues('item', $item, $fields);

    DB::table('media')->where('id', 11)->update(['file_name' => 'cover-v2.jpg']);

    app(BuilderFieldValueMediaMetadataSync::class)->syncForMedia(11);

    $loaded = app(CustomFieldsManager::class)->loadCachedValues('item', $item, $fields);

    expect($loaded['hero_image']['file_name'])->toBe('cover-v2.jpg');
});

it('updates gallery snapshots when media metadata changes', function (): void {
    MediaTestHelpers::seedMedia(20, 'one.jpg');
    MediaTestHelpers::seedMedia(21, 'two.jpg');

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => 1,
        'field_name' => 'photos',
        'value_json' => [
            '1' => ['id' => 20, 'file_name' => 'one.jpg', 'title' => null, 'alt' => null, 'description' => null, 'internal_note' => null],
            '2' => ['id' => 21, 'file_name' => 'two.jpg', 'title' => 'Old', 'alt' => null, 'description' => null, 'internal_note' => null],
        ],
    ]);

    DB::table('media')->where('id', 21)->update(['file_name' => 'two-v2.jpg']);

    app(BuilderFieldValueMediaMetadataSync::class)->syncForMedia(21);

    expect(FieldValue::query()->first()?->value_json['2']['file_name'])->toBe('two-v2.jpg');
});
