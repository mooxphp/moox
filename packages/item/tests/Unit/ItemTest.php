<?php

use Moox\Item\Models\Item;

it('can create an item', function () {
    $item = Item::create([
        'title' => 'Test Item',
        'description' => 'Test Description',
        'custom_properties' => json_encode(['test' => 'test']),
    ]);
    expect($item)->toBeTruthy();
    expect($item->title)->toBe('Test Item');
    expect($item->description)->toBe('Test Description');
    expect($item->custom_properties)->toBe(json_encode(['test' => 'test']));
});

it('can create an ite with a factory', function () {
    $item = Item::factory()->create();
    expect($item)->toBeInstanceOf(Item::class);
});
