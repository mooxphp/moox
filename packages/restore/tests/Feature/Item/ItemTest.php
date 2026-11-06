<?php

use Illuminate\Support\Facades\Schema;
use Moox\Restore\Models\RestorDestination;

test('restore_destination Table exists with correct columns', function () {
    expect(Schema::hasTable('restore_destination'))->toBeTrue();
    expect(Schema::hasColumns('restore_destination', ['id', 'title', 'slug', 'content', 'status', 'type', 'deleted_at', 'created_at', 'updated_at', 'featured_image_url', 'gallery_image_urls', 'author_id', 'publish_at']))->toBeTrue();
});

test('item can be manually created', function () {
    $item = new RestorDestination;
    $item->title = 'Test Title';
    $item->slug = 'test-title';
    $item->content = 'Test Content';
    $item->status = 'published';
    $item->type = 'default';
    $item->save();

    expect($item->id)->not->toBeNull();
    expect($item->title)->toBe('Test Title');
});

test('item can be create with factory', function () {
    $restore_destination = RestorDestination::factory()->count(10)->create();
    expect($restore_destination->count())->toBe(10);
});
