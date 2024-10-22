<?php

use Illuminate\Support\Facades\Schema;
use Moox\Category\Models\Category;

test('categories Table exists with correct columns', function () {
    expect(Schema::hasTable('categories'))->toBeTrue();
    expect(Schema::hasColumns('categories', ['id', 'title', 'slug', 'content', 'status', 'type', 'deleted_at', 'created_at', 'updated_at', 'featured_image_url', 'gallery_image_urls', 'author_id', 'publish_at']))->toBeTrue();
});

test('item can be manually created', function () {
    $item = new Category;
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
    $categories = Category::factory()->count(10)->create();
    expect($categories->count())->toBe(10);
});
