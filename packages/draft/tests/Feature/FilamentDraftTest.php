<?php

use Moox\Draft\Models\Draft;
use Moox\Draft\Moox\Entities\Drafts\Draft\Pages\EditDraft;
use Moox\Draft\Moox\Entities\Drafts\Draft\Pages\ListDrafts;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $user = $this->createTestUser();
    $this->actingAs($user);
});

it('can list the drafts', function () {
    $drafts = Draft::factory()->count(5)->create();

    livewire(ListDrafts::class)
        ->assertOk()
        ->assertCanSeeTableRecords($drafts);
});
it('can sort by columns and show correct translations', function () {
    $drafts = Draft::factory()->count(10)->create();

    // Test sorting with English locale
    livewire(ListDrafts::class, ['lang' => 'en'])
        ->assertCanSeeTableRecords($drafts)
        ->sortTable('title')
        ->assertCanSeeTableRecords(records: $drafts, inOrder: true)
        ->assertSee($drafts[0]->title);

    // Test sorting with French locale
    livewire(ListDrafts::class, ['lang' => 'fr'])
        ->assertCanSeeTableRecords($drafts)
        ->sortTable('title')
        ->assertCanSeeTableRecords(records: $drafts, inOrder: true)
        ->assertSee($drafts[0]->title);
});

// it('can load the edit draft page', function () {
//     $draft = Draft::factory()->create();

//     livewire(EditDraft::class, [
//         'record' => $draft->id,
//     ])
//         ->assertOk()
//         ->assertSchemaStateSet([

//         ]);
// });

// it('can update draft to published', function () {

//     $draft = Draft::factory()->create();
//     livewire(EditDraft::class, [
//         'record' => $draft->id,
//     ])
//         ->fillForm([

//         ])
//         ->call('save')
//         ->assertNotified();

//     assertDatabaseHas(Draft::class, [
//         'id' => $draft->id,
//         'status' => 'published',
//     ]);
// });
