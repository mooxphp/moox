<?php

use Moox\Builder\Models\Item;
use Moox\Builder\Resources\ItemResource;
use Moox\Builder\Resources\ItemResource\Pages\ListItems;

use function Pest\Livewire\livewire;

/*_____________________________________________________

    table
_______________________________________________________ */

it('can render ItemResource', function () {
    $this
        ->get(ItemResource::getUrl('index'))
        ->assertSuccessful();
});

it('can list Items', function () {
    $items = Item::factory()->count(10)->create();

    livewire(ListItems::class)
        ->assertCanSeeTableRecords($items);
});

//it('can edit an item', function () {
//    $item = Item::factory()->create();
//
//    livewire(ListItems::class)
//       ->call('edit', $item->id)
//        ->assertRedirectTo(ItemResource::getUrl('edit', ['record' => $item->id]));
//});

/*
it('can render all tabs', function () {
    config(['builder.resources.builder.tabs' => ['all']]);

    $this
        ->get(ItemResource::getUrl('index'))
        ->assertSee('Items');
    // ->assertSee('All')
    // ->assertDontSee('Published')
    // ->assertDontSee('Draft')
    // ->assertDontSee('Scheduled')
    // ->assertDontSee('Trashed');
});
*/

it('can render index view ', function () {
    $this->get(ItemResource::getUrl('index'))->assertSuccessful();
});
it('can render edit page', function () {
    $this->get(ItemResource::getUrl('edit', [
        'record' => Item::factory()->create(),
    ]))->assertSuccessful();
});

it('can retrieve data', function () {
    $item = Item::factory()->create();

    livewire(ItemResource\Pages\EditItem::class, [
        'record' => $item->getRouteKey(),
    ])
        ->assertFormSet([
            'title' => $item->title,
            'content' => $item->content,
            'status' => $item->status,
            'type' => $item->type,
        ]);
});
it('can render view', function () {
    $this->get(ItemResource::getUrl('view', [
        'record' => Item::factory()->create(),
    ]))->assertSuccessful();
});
