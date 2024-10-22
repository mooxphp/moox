<?php

use Moox\Category\Models\Category;
use Moox\Category\Resources\CategoryResource;
use Moox\Category\Resources\CategoryResource\Pages\ListCategorys;

use function Pest\Livewire\livewire;

/*_____________________________________________________

    table
_______________________________________________________ */

it('can render CategoryResource', function () {
    $this
        ->get(CategoryResource::getUrl('index'))
        ->assertSuccessful();
});

it('can list Categorys', function () {
    $categories = Category::factory()->count(10)->create();

    livewire(ListCategorys::class)
        ->assertCanSeeTableRecords($categories);
});

//it('can edit an item', function () {
//    $item = Category::factory()->create();
//
//    livewire(ListCategorys::class)
//       ->call('edit', $item->id)
//        ->assertRedirectTo(CategoryResource::getUrl('edit', ['record' => $item->id]));
//});

/*
it('can render all tabs', function () {
    config(['category.resources.category.tabs' => ['all']]);

    $this
        ->get(CategoryResource::getUrl('index'))
        ->assertSee('Categorys');
    // ->assertSee('All')
    // ->assertDontSee('Published')
    // ->assertDontSee('Draft')
    // ->assertDontSee('Scheduled')
    // ->assertDontSee('Trashed');
});
*/

it('can render index view ', function () {
    $this->get(CategoryResource::getUrl('index'))->assertSuccessful();
});
it('can render edit page', function () {
    $this->get(CategoryResource::getUrl('edit', [
        'record' => Category::factory()->create(),
    ]))->assertSuccessful();
});

it('can retrieve data', function () {
    $item = Category::factory()->create();

    livewire(CategoryResource\Pages\EditCategory::class, [
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
    $this->get(CategoryResource::getUrl('view', [
        'record' => Category::factory()->create(),
    ]))->assertSuccessful();
});
