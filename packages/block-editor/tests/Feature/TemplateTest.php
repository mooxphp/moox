<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\BlockEditor\Models\Template;

uses(RefreshDatabase::class);

it('casts content to array', function (): void {
    $template = Template::query()->create([
        'name' => 'Model Cast Template',
        'slug' => 'model-cast-template',
        'content' => [
            ['id' => '1', 'type' => 'paragraph', 'content' => 'Hello'],
        ],
    ]);

    $freshTemplate = Template::query()->findOrFail($template->id);

    expect($freshTemplate->content)->toBeArray()
        ->and($freshTemplate->content[0]['type'])->toBe('paragraph');
});

it('allows mass assignment only for fillable fields', function (): void {
    $template = new Template;
    $template->fill([
        'name' => 'Fillable Template',
        'slug' => 'fillable-template',
        'content' => [],
        'not_fillable' => 'blocked',
    ]);

    expect($template->name)->toBe('Fillable Template')
        ->and($template->slug)->toBe('fillable-template')
        ->and($template->content)->toBeArray()
        ->and(isset($template->not_fillable))->toBeFalse();
});
