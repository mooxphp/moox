<?php

use Moox\Draft\Models\Draft;
use function Pest\Livewire\livewire;
use Moox\Draft\Moox\Entities\Drafts\Draft\Pages\ListDrafts;

it('can load the page', function () {
    $user = $this->createTestUser();

    $this->actingAs($user);

    $drafts = Draft::factory()->count(5)->create();

    livewire(ListDrafts::class)
        ->assertOk()
        ->assertCanSeeTableRecords($drafts);
});