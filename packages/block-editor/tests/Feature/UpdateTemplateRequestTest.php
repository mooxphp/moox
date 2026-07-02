<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\BlockEditor\Models\Template;

uses(RefreshDatabase::class);

it('validates unique slug on update when used by another template', function (): void {
    $user = User::factory()->create();

    $first = Template::query()->create([
        'name' => 'First',
        'slug' => 'first',
        'content' => [],
    ]);

    $second = Template::query()->create([
        'name' => 'Second',
        'slug' => 'second',
        'content' => [],
    ]);

    $this->actingAs($user)
        ->putJson(route('moox-editor.templates.update', ['template' => $second->id]), [
            'slug' => 'first',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);

    expect($first->fresh()?->slug)->toBe('first');
});

it('allows keeping the same slug on update', function (): void {
    $user = User::factory()->create();

    $template = Template::query()->create([
        'name' => 'Landing',
        'slug' => 'landing',
        'content' => [],
    ]);

    $this->actingAs($user)
        ->putJson(route('moox-editor.templates.update', ['template' => $template->id]), [
            'name' => 'Landing Updated',
            'slug' => 'landing',
        ])
        ->assertOk()
        ->assertJsonPath('name', 'Landing Updated')
        ->assertJsonPath('slug', 'landing');
});
