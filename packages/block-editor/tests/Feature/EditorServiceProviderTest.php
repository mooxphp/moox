<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Moox\BlockEditor\Models\Template;
use Moox\BlockEditor\Policies\TemplatePolicy;

it('registers template policy', function (): void {
    expect(Gate::getPolicyFor(Template::class))->toBeInstanceOf(TemplatePolicy::class);
});

it('registers editor api routes', function (): void {
    $route = Route::getRoutes()->getByName('moox-editor.templates.index');

    expect($route)->not->toBeNull()
        ->and($route?->uri())->toBe('api/editor/v1/templates');
});
