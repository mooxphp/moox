<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\BlockEditor\Models\Template;

uses(RefreshDatabase::class);

it('shows the templates index for authenticated users', function (): void {
    $user = User::factory()->create();

    Template::query()->create([
        'name' => 'Fixture Template',
        'slug' => 'fixture-template',
        'content' => [['id' => '1', 'type' => 'paragraph', 'content' => 'Hello']],
        'meta' => ['source' => 'test'],
    ]);

    $this->actingAs($user)
        ->get('/dashboard/templates')
        ->assertSuccessful()
        ->assertSee('Fixture Template');
});

it('redirects guests to login for the templates index', function (): void {
    $this->get('/dashboard/templates')
        ->assertRedirect(route('filament.dashboard.auth.login'));
});
