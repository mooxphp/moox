<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Moox\BlockEditor\Models\Template;
use Moox\BlockEditor\Policies\TemplatePolicy;

it('registers template policy', function (): void {
    expect(Gate::getPolicyFor(Template::class))->toBeInstanceOf(TemplatePolicy::class);
});

it('registers editor api routes', function (): void {
    $templatesRoute = Route::getRoutes()->getByName('moox-editor.templates.index');
    $dynamicFeedsRoute = Route::getRoutes()->getByName('moox-editor.dynamic-feeds.sources');

    expect($templatesRoute)->not->toBeNull()
        ->and($templatesRoute?->uri())->toBe('api/editor/v1/templates')
        ->and($dynamicFeedsRoute)->not->toBeNull()
        ->and($dynamicFeedsRoute?->uri())->toBe('api/editor/v1/dynamic-feeds/sources');
});
