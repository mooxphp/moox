<?php

/** @var \Moox\Item\Tests\TestCase $this */

use Moox\Item\Models\Item;
use Moox\Item\Moox\Entities\Items\Item\Pages\CreateItem;
use Moox\Item\Moox\Entities\Items\Item\Pages\EditItem;
use Moox\Item\Moox\Entities\Items\Item\Pages\ListItems;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $user = $this->createTestUser();
    $this->actingAs($user);
});

it('can render ListItempage', function () {
    livewire(ListItems::class)->assertSuccessful();
});

it('can render item title', function () {
    Item::factory()->count(10)->create();

    livewire(ListItems::class)
        ->assertCanRenderTableColumn('title');
});

it('has expected table columns', function () {
    livewire(ListItems::class)
        ->assertTableColumnExists('title')
        ->assertTableColumnExists('description')
        ->assertTableColumnExists('custom_properties')
        ->assertTableColumnExists('created_at');
});

it('lists and searches items by title', function () {
    Item::factory()->create(['title' => 'Alpha']);
    Item::factory()->create(['title' => 'Beta']);

    livewire(ListItems::class)
        ->loadTable()
        ->assertCountTableRecords(2)
        ->set('tableSearch', 'Alpha')
        ->loadTable()
        ->assertCountTableRecords(1);
});

it('has default sort by title desc', function () {
    Item::factory()->create(['title' => 'A']);
    Item::factory()->create(['title' => 'Z']);

    $component = livewire(ListItems::class)->loadTable();

    // Ensure the first visible record is the one with highest title (desc)
    $first = Item::orderBy('title', 'desc')->first();
    $component->assertCanSeeTableRecords([$first]);
});

it('create form contains expected fields', function () {
    livewire(CreateItem::class)
        ->assertFormExists('form')
        ->assertFormFieldExists('title', 'form')
        ->assertFormFieldExists('description', 'form');
});

it('can create an item via form', function () {
    livewire(CreateItem::class)
        ->fillForm([
            'title' => 'New Item',
            'description' => 'Desc',
        ], 'form')
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Item::where('title', 'New Item')->exists())->toBeTrue();
});

it('edit form contains expected fields', function () {
    $item = Item::factory()->create(['title' => 'Old', 'description' => 'Old D']);

    livewire(EditItem::class, ['record' => $item->getKey()])
        ->assertFormExists('form')
        ->assertFormFieldExists('title', 'form')
        ->assertFormFieldExists('description', 'form');
});
