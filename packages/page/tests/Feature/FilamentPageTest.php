<?php

use Moox\Page\Models\Page;
use Moox\Page\Resources\PageResource\Pages\ListPages;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $user = $this->createTestUser();
    $this->actingAs($user);
});

it('can list the drafts', function () {
    $pages = Page::factory()->count(5)->create();

    livewire(ListPages::class)
        ->assertOk()
        ->assertCanSeeTableRecords($pages);
});
it('can sort by columns and show correct translations', function () {
    $pages = Page::factory()->count(10)->create();

    // Test sorting with English locale
    livewire(ListPages::class, ['lang' => 'en'])
        ->assertCanSeeTableRecords($pages)
        ->sortTable('title')
        ->assertCanSeeTableRecords(records: $pages, inOrder: true)
        ->assertSee($pages[0]->title);

    // Test sorting with French locale
    livewire(ListPages::class, ['lang' => 'fr'])
        ->assertCanSeeTableRecords($pages)
        ->sortTable('title')
        ->assertCanSeeTableRecords(records: $pages, inOrder: true)
        ->assertSee($pages[0]->title);
});

