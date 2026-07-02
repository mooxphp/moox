<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\BlockEditor\Models\Template;

uses(RefreshDatabase::class);

it('validates required name on store', function (): void {
    $user = User::factory()->create();
    $indexUrl = route('moox-editor.templates.index');

    $this->actingAs($user)
        ->postJson($indexUrl, [
            'slug' => 'missing-name',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('validates unique slug on store', function (): void {
    $user = User::factory()->create();
    $indexUrl = route('moox-editor.templates.index');

    Template::query()->create([
        'name' => 'Existing',
        'slug' => 'home',
        'content' => [],
    ]);

    $this->actingAs($user)
        ->postJson($indexUrl, [
            'name' => 'Another',
            'slug' => 'home',
            'content' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});
