<?php

use Moox\Tag\Models\Tag;
use Moox\Tag\Resources\TagResource;
use Moox\Tag\Resources\TagResource\Pages\ListTags;

use function Pest\Livewire\livewire;

/*_____________________________________________________

    table
_______________________________________________________ */

it('can render TagResource', function () {
    $this
        ->get(TagResource::getUrl('index'))
        ->assertSuccessful();
});

it('can list Tags', function () {
    $tags = Tag::factory()->count(10)->create();

    livewire(ListTags::class)
        ->assertCanSeeTableRecords($tags);
});

//it('can edit an item', function () {
//    $item = Tag::factory()->create();
//
//    livewire(ListTags::class)
//       ->call('edit', $item->id)
//        ->assertRedirectTo(TagResource::getUrl('edit', ['record' => $item->id]));
//});

/*
it('can render all tabs', function () {
    config(['tag.resources.tag.tabs' => ['all']]);

    $this
        ->get(TagResource::getUrl('index'))
        ->assertSee('Tags');
    // ->assertSee('All')
    // ->assertDontSee('Published')
    // ->assertDontSee('Draft')
    // ->assertDontSee('Scheduled')
    // ->assertDontSee('Trashed');
});
*/

it('can render index view ', function () {
    $this->get(TagResource::getUrl('index'))->assertSuccessful();
});
it('can render edit page', function () {
    $this->get(TagResource::getUrl('edit', [
        'record' => Tag::factory()->create(),
    ]))->assertSuccessful();
});

it('can retrieve data', function () {
    $item = Tag::factory()->create();

    livewire(TagResource\Pages\EditTag::class, [
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
    $this->get(TagResource::getUrl('view', [
        'record' => Tag::factory()->create(),
    ]))->assertSuccessful();
});
