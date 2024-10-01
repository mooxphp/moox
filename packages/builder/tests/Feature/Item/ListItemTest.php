<?php

use Moox\Builder\Models\Item;
use Moox\Builder\Resources\ItemResource;
use Moox\Builder\Resources\ItemResource\Pages\ListItem;

use function Pest\Livewire\livewire;

it('can render ItemResource', function () {
    $this
        ->get(ItemResource::getUrl('index'))
        ->assertSuccessful();
});

it('can list Items', function () {
    $posts = Item::factory()->count(10)->create();

    livewire(ListItem::class)
        ->assertCanSeeTableRecords($posts);
});
