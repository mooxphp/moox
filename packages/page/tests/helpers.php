<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Moox\Page\Http\Controllers\PageController;
use Moox\Page\Models\Page;

function createPackageTestPage(
    string $layout,
    string $slug,
    bool $isStartpage = false,
    string $locale = 'en',
    string $translationStatus = 'published',
    ?DateTimeInterface $publishedAt = null,
): Page {
    $page = Page::query()->create([
        'is_active' => true,
        'is_startpage' => $isStartpage,
        'layout' => $layout,
        'uuid' => (string) Str::uuid(),
        'ulid' => (string) Str::ulid(),
    ]);

    $page->translations()->create([
        'locale' => $locale,
        'title' => 'Test Page',
        'slug' => $slug,
        'permalink' => '/'.$slug,
        'translation_status' => $translationStatus,
        'published_at' => $publishedAt ?? now(),
    ]);

    return $page;
}

function callPageControllerIndex(): mixed
{
    $request = Request::create('/', 'GET');
    $route = app('router')->getRoutes()->match($request);
    $request->setRouteResolver(fn () => $route);
    app()->instance('request', $request);

    return app(PageController::class)->index();
}

function callPageControllerShow(string $uri): mixed
{
    $request = Request::create($uri, 'GET');
    $route = app('router')->getRoutes()->match($request);
    $request->setRouteResolver(fn () => $route);
    app()->instance('request', $request);

    $slug = $route->parameter('slug');

    if (! is_string($slug) || $slug === '') {
        $slug = trim(parse_url($uri, PHP_URL_PATH) ?: '', '/');
    }

    return app(PageController::class)->show($slug);
}
