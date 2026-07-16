<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/MediaTestHelpers.php';

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Services\BuilderMediaUsageSync;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldValuePurger;
use Moox\Builder\Support\MediaIntegration;
use Moox\Builder\Tests\Support\MediaTestHelpers;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\TestCase;
use Moox\Media\Models\MediaUsable;

uses(TestCase::class);

beforeEach(function (): void {
    if (! MediaIntegration::isAvailable()) {
        $this->markTestSkipped('moox/media is not installed.');
    }

    $this->createItemsTable();

    Schema::dropIfExists('media_usables');

    Schema::create('media_usables', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('media_id');
        $table->morphs('media_usable');
        $table->timestamps();
    });
});

it('creates media usables when an image custom field is saved', function (): void {
    MediaTestHelpers::seedMedia(42, 'hero.jpg');

    $item = TestItem::query()->create(['title' => 'Post']);
    $field = new FieldDefinition('hero_image', 'Hero image', 'image');

    app(CustomFieldsManager::class)->saveValues('item', $item, [
        'hero_image' => [
            'id' => 42,
            'file_name' => 'hero.jpg',
            'title' => null,
            'alt' => null,
            'description' => null,
            'internal_note' => null,
        ],
    ], collect([$field]));

    expect(MediaUsable::query()
        ->where('media_id', 42)
        ->where('media_usable_id', $item->getKey())
        ->where('media_usable_type', TestItem::class)
        ->exists())->toBeTrue();

    $usable = MediaUsable::query()->first();

    expect(MediaUsable::query()->count())->toBe(1)
        ->and($usable?->media_id)->toBe(42)
        ->and($usable?->media_usable_id)->toBe($item->getKey())
        ->and($usable?->media_usable_type)->toBe(TestItem::class);
});

it('creates media usables when a file custom field is saved', function (): void {
    MediaTestHelpers::seedMedia(43, 'brochure.pdf', 'application/pdf');

    $item = TestItem::query()->create(['title' => 'Post']);
    $field = new FieldDefinition('attachment', 'Attachment', 'file');

    app(CustomFieldsManager::class)->saveValues('item', $item, [
        'attachment' => [43],
    ], collect([$field]));

    expect(MediaUsable::query()
        ->where('media_id', 43)
        ->where('media_usable_id', $item->getKey())
        ->where('media_usable_type', TestItem::class)
        ->exists())->toBeTrue();
});

it('removes stale media usables when an image field is cleared', function (): void {
    MediaTestHelpers::seedMedia(42, 'hero.jpg');

    $item = TestItem::query()->create(['title' => 'Post']);
    $field = new FieldDefinition('hero_image', 'Hero image', 'image');

    MediaUsable::query()->create([
        'media_id' => 42,
        'media_usable_id' => $item->getKey(),
        'media_usable_type' => TestItem::class,
    ]);

    app(CustomFieldsManager::class)->saveValues('item', $item, [
        'hero_image' => [],
    ], collect([$field]));

    expect(MediaUsable::query()->count())->toBe(0);
});

it('purges media usables when a record is deleted', function (): void {
    $item = TestItem::query()->create(['title' => 'Post']);

    MediaUsable::query()->create([
        'media_id' => 7,
        'media_usable_id' => $item->getKey(),
        'media_usable_type' => TestItem::class,
    ]);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $item->getKey(),
        'field_name' => 'hero_image',
        'value_json' => ['id' => 7, 'file_name' => 'hero.jpg'],
    ]);

    app(FieldValuePurger::class)->purgeForRecord('item', $item->getKey(), $item);

    expect(MediaUsable::query()->count())->toBe(0);
});

it('collects media ids from all image fields on a record', function (): void {
    $item = TestItem::query()->create(['title' => 'Post']);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $item->getKey(),
        'field_name' => 'hero_image',
        'value_json' => ['id' => 1, 'file_name' => 'a.jpg'],
    ]);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $item->getKey(),
        'field_name' => 'thumbnail',
        'value_json' => ['id' => 2, 'file_name' => 'b.jpg'],
    ]);

    app(BuilderMediaUsageSync::class)->syncForRecord('item', $item, collect([
        new FieldDefinition('hero_image', 'Hero', 'image'),
        new FieldDefinition('thumbnail', 'Thumb', 'image'),
    ]));

    expect(MediaUsable::query()->pluck('media_id')->all())->toBe([1, 2]);
});
