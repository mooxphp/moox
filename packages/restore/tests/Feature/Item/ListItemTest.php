<?php

use Moox\Restore\Models\RestorDestination;
use Moox\Restore\Resources\RestorDestinationResource;
use Moox\Restore\Resources\RestorDestinationResource\Pages\ListRestorDestinations;

use function Pest\Livewire\livewire;

/*_____________________________________________________

    table
_______________________________________________________ */

it('can render RestorDestinationResource', function () {
    $this
        ->get(RestorDestinationResource::getUrl('index'))
        ->assertSuccessful();
});

it('can list RestorDestinations', function () {
    $restore_destination = RestorDestination::factory()->count(10)->create();

    livewire(ListRestorDestinations::class)
        ->assertCanSeeTableRecords($restore_destination);
});

// it('can edit an item', function () {
//    $item = RestorDestination::factory()->create();
//
//    livewire(ListRestorDestinations::class)
//       ->call('edit', $item->id)
//        ->assertRedirectTo(RestorDestinationResource::getUrl('edit', ['record' => $item->id]));
// });

/*
it('can render all tabs', function () {
    config(['restore.resources.restore.tabs' => ['all']]);

    $this
        ->get(RestorDestinationResource::getUrl('index'))
        ->assertSee('RestorDestinations');
    // ->assertSee('All')
    // ->assertDontSee('Published')
    // ->assertDontSee('Draft')
    // ->assertDontSee('Scheduled')
    // ->assertDontSee('Trashed');
});
*/

it('can render index view ', function () {
    $this->get(RestorDestinationResource::getUrl('index'))->assertSuccessful();
});
it('can render edit page', function () {
    $this->get(RestorDestinationResource::getUrl('edit', [
        'record' => RestorDestination::factory()->create(),
    ]))->assertSuccessful();
});

it('can retrieve data', function () {
    $item = RestorDestination::factory()->create();

    livewire(RestorDestinationResource\Pages\EditRestorDestination::class, [
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
    $this->get(RestorDestinationResource::getUrl('view', [
        'record' => RestorDestination::factory()->create(),
    ]))->assertSuccessful();
});
