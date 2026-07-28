<?php

use Moox\Audit\Filament\RelationManagers\ActivitiesRelationManager;
use Moox\Audit\Models\Activity;
use Moox\Item\Models\Item;
use Moox\Item\Resources\ItemResource;

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

it('logs item create and update events when audit is installed', function () {
    $item = Item::create([
        'title' => 'Audited Item',
        'description' => 'First description',
    ]);

    $item->update([
        'description' => 'Updated description',
    ]);

    expect(Activity::query()
        ->where('subject_type', Item::class)
        ->where('subject_id', $item->getKey())
        ->where('event', 'created')
        ->exists())->toBeTrue()
        ->and(Activity::query()
            ->where('subject_type', Item::class)
            ->where('subject_id', $item->getKey())
            ->where('event', 'updated')
            ->exists())->toBeTrue();
});

it('exposes the audit activity relation on the item resource', function () {
    expect(ItemResource::getRelations())
        ->toContain(ActivitiesRelationManager::class);
});
