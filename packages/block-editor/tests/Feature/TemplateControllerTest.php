<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\BlockEditor\Models\Template;

uses(RefreshDatabase::class);

it('allows authenticated users to manage templates via api', function (): void {
    $user = User::factory()->create();
    $indexUrl = route('moox-editor.templates.index');

    $createResponse = $this->actingAs($user)->postJson($indexUrl, [
        'name' => 'Landing Page',
        'slug' => 'landing-page',
        'content' => [
            [
                'id' => '1',
                'type' => 'paragraph',
                'content' => '<p>Hello<script>alert(1)</script></p>',
            ],
        ],
    ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('name', 'Landing Page')
        ->assertJsonPath('slug', 'landing-page');

    $templateId = $createResponse->json('id');
    $template = Template::query()->findOrFail($templateId);

    expect($template->content[0]['type'])->toBe('paragraph');

    $this->actingAs($user)
        ->getJson($indexUrl)
        ->assertOk()
        ->assertJsonFragment(['id' => $templateId]);

    $this->actingAs($user)
        ->putJson(route('moox-editor.templates.update', ['template' => $templateId]), [
            'name' => 'Landing Page Updated',
        ])
        ->assertOk()
        ->assertJsonPath('name', 'Landing Page Updated');

    $this->actingAs($user)
        ->deleteJson(route('moox-editor.templates.destroy', ['template' => $templateId]))
        ->assertNoContent();

    $this->assertDatabaseMissing('editor_templates', ['id' => $templateId]);
});

it('denies guests on templates api routes', function (): void {
    $this->getJson(route('moox-editor.templates.index'))->assertUnauthorized();
    $this->postJson(route('moox-editor.templates.index'), ['name' => 'Guest'])->assertUnauthorized();
});

it('allows guest access when middleware and authorization are disabled', function (): void {
    config()->set('moox-editor.api.authorization', false);
    $this->withoutMiddleware();

    Template::query()->create([
        'name' => 'Public Template',
        'slug' => 'public-template',
        'content' => [],
    ]);

    $this->getJson(route('moox-editor.templates.index'))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Public Template');
});

it('supports partial updates without changing other fields', function (): void {
    $user = User::factory()->create();

    $template = Template::query()->create([
        'name' => 'Before Name',
        'slug' => 'before-slug',
        'content' => [['id' => '1', 'type' => 'paragraph', 'content' => 'Before']],
    ]);

    $this->actingAs($user)
        ->putJson(route('moox-editor.templates.update', ['template' => $template->id]), [
            'name' => 'After Name',
        ])
        ->assertOk()
        ->assertJsonPath('name', 'After Name')
        ->assertJsonPath('slug', 'before-slug');

    $template->refresh();

    expect($template->name)->toBe('After Name')
        ->and($template->slug)->toBe('before-slug')
        ->and($template->content[0]['content'])->toBe('Before');
});

it('rejects invalid content payload type', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('moox-editor.templates.index'), [
            'name' => 'Invalid Payload',
            'slug' => 'invalid-payload',
            'content' => 'this-should-be-an-array',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content']);
});

it('returns paginated and filtered template index results', function (): void {
    $user = User::factory()->create();

    Template::query()->create([
        'name' => 'Alpha Template',
        'slug' => 'alpha-template',
        'content' => [],
    ]);
    Template::query()->create([
        'name' => 'Beta Template',
        'slug' => 'beta-template',
        'content' => [],
    ]);
    Template::query()->create([
        'name' => 'Gamma Layout',
        'slug' => 'gamma-layout',
        'content' => [],
    ]);

    $this->actingAs($user)
        ->getJson(route('moox-editor.templates.index', [
            'search' => 'template',
            'per_page' => 1,
            'sort' => 'name',
            'direction' => 'asc',
        ]))
        ->assertOk()
        ->assertJsonPath('current_page', 1)
        ->assertJsonPath('per_page', 1)
        ->assertJsonPath('total', 2)
        ->assertJsonPath('data.0.name', 'Alpha Template');
});

it('validates index query parameters', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('moox-editor.templates.index', [
            'per_page' => 1000,
            'sort' => 'unknown',
            'direction' => 'sideways',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page', 'sort', 'direction']);
});
