<?php

declare(strict_types=1);

namespace Moox\Page\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Moox\Page\Contracts\PageContentRenderer;
use Moox\Page\Models\Page;
use Moox\Page\Support\PageLayoutResolver;
use Moox\Page\Support\PageLocaleResolver;
use Moox\Page\Support\PageResponseCache;
use Moox\Page\Support\PublishedPageQuery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends Controller
{
    public function __construct(
        private readonly PageContentRenderer $contentRenderer,
        private readonly PageLayoutResolver $layoutResolver,
        private readonly PageLocaleResolver $localeResolver,
        private readonly PublishedPageQuery $publishedPageQuery,
        private readonly PageResponseCache $responseCache,
    ) {}

    public function index(): View|Response
    {
        $locale = $this->localeResolver->resolve();

        return $this->responseCache->remember(
            locale: $locale,
            slug: 'home',
            resolver: function () use ($locale): View|Response {
                $homepage = $this->publishedPageQuery->findHomepage($locale);

                if ($homepage !== null) {
                    return $this->renderPage($homepage, $locale);
                }

                $pages = $this->publishedPageQuery->all($locale);

                return view('default.index', [
                    'pages' => $pages,
                    'locale' => $locale,
                    'navigationPages' => $pages,
                ]);
            },
        );
    }

    public function show(string $slug): View|Response
    {
        if ($this->isReservedSlug($slug)) {
            throw new NotFoundHttpException;
        }

        $locale = $this->localeResolver->resolve();

        $page = $this->publishedPageQuery->findBySlug($slug, $locale);

        if ($page === null) {
            throw new NotFoundHttpException;
        }

        return $this->responseCache->remember(
            locale: $locale,
            slug: $slug,
            resolver: fn (): View => $this->renderPage($page, $locale),
        );
    }

    private function renderPage(Page $page, string $locale): View
    {
        app()->setLocale($locale);

        $translation = $page->translate($locale, withFallback: false);

        if ($translation === null) {
            throw new NotFoundHttpException;
        }

        $layout = $this->layoutResolver->resolveLayout($page);
        $view = $this->layoutResolver->resolveView($page);

        return view($view, [
            'page' => $page,
            'translation' => $translation,
            'locale' => $locale,
            'navigationPages' => $this->publishedPageQuery->forLayout($locale, $layout)
                ->reject(fn (Page $navigationPage): bool => (bool) $navigationPage->is_startpage),
            'contentHtml' => $this->contentRenderer->render($translation->content, $locale),
        ]);
    }

    private function isReservedSlug(string $slug): bool
    {
        $reservedSlugs = config('page.reserved_slugs', []);

        if (! is_array($reservedSlugs)) {
            return false;
        }

        return in_array(strtolower($slug), array_map('strtolower', $reservedSlugs), true);
    }
}
