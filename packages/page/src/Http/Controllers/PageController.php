<?php

declare(strict_types=1);

namespace Moox\Page\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Routing\Controller;
use Moox\Localization\Models\Localization;
use Moox\Page\Contracts\PageContentRenderer;
use Moox\Page\Models\Page;
use Moox\Page\Support\PageLayoutResolver;
use Moox\Page\Support\PageModels;
use Moox\Page\Support\PagePermalink;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends Controller
{
    public function __construct(
        private readonly PageContentRenderer $contentRenderer,
        private readonly PageLayoutResolver $layoutResolver,
    ) {}

    public function index(): View
    {
        $locale = $this->resolveActiveLocale();

        $homepage = $this->findHomepage($locale);

        if ($homepage !== null) {
            return $this->renderPage($homepage, $locale);
        }

        $pages = $this->publishedPages($locale);

        return view('default.index', [
            'pages' => $pages,
            'locale' => $locale,
            'navigationPages' => $pages,
        ]);
    }

    public function show(string $slug): View
    {
        if ($this->isReservedSlug($slug)) {
            throw new NotFoundHttpException;
        }

        $locale = $this->resolveActiveLocale();

        $page = $this->findPublishedPage($slug, $locale);

        if ($page === null) {
            throw new NotFoundHttpException;
        }

        return $this->renderPage($page, $locale);
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
            'navigationPages' => $this->navigationPages($locale, $layout)
                ->reject(fn (Page $navigationPage): bool => (bool) $navigationPage->is_startpage),
            'contentHtml' => $this->contentRenderer->render($translation->content, $locale),
        ]);
    }

    private function findHomepage(string $locale): ?Page
    {
        return PageModels::page()::query()
            ->homepage()
            ->where('is_active', true)
            ->whereHas('translations', function ($query) use ($locale): void {
                $query
                    ->where('locale', $locale)
                    ->where('translation_status', 'published')
                    ->whereNotNull('published_at');
            })
            ->first();
    }

    private function findPublishedPage(string $slug, string $locale): ?Page
    {
        $lookupValues = PagePermalink::lookupCandidates($slug);

        return PageModels::page()::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($query) use ($locale, $lookupValues): void {
                $query
                    ->where('locale', $locale)
                    ->where('translation_status', 'published')
                    ->whereNotNull('published_at')
                    ->where(function ($query) use ($lookupValues): void {
                        $query
                            ->whereIn('slug', $lookupValues)
                            ->orWhereIn('permalink', $lookupValues);
                    });
            })
            ->first();
    }

    private function isReservedSlug(string $slug): bool
    {
        $reservedSlugs = config('page.reserved_slugs', []);

        if (! is_array($reservedSlugs)) {
            return false;
        }

        return in_array(strtolower($slug), array_map('strtolower', $reservedSlugs), true);
    }

    /**
     * @return Collection<int, Page>
     */
    private function publishedPages(string $locale): Collection
    {
        return PageModels::page()::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($query) use ($locale): void {
                $query
                    ->where('locale', $locale)
                    ->where('translation_status', 'published')
                    ->whereNotNull('published_at');
            })
            ->with(['translations' => fn ($query) => $query->where('locale', $locale)])
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Page>
     */
    private function navigationPages(string $locale, string $layout = 'default'): Collection
    {
        return PageModels::page()::query()
            ->where('is_active', true)
            ->where('layout', $layout)
            ->whereHas('translations', function ($query) use ($locale): void {
                $query
                    ->where('locale', $locale)
                    ->where('translation_status', 'published')
                    ->whereNotNull('published_at');
            })
            ->with(['translations' => fn ($query) => $query->where('locale', $locale)])
            ->orderBy('id')
            ->get();
    }

    private function resolveActiveLocale(): string
    {
        foreach ($this->localeCandidates() as $candidate) {
            $resolved = $this->resolveLocaleVariant($candidate);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        $defaultLocalization = Localization::query()
            ->where('is_default', true)
            ->first();

        return $defaultLocalization?->locale_variant ?? app()->getLocale();
    }

    /**
     * @return list<string>
     */
    private function localeCandidates(): array
    {
        return array_values(array_filter([
            request()->query('lang'),
            request()->input('lang'),
            session('locale'),
            request()->cookie('switch_locale'),
        ], fn (mixed $value): bool => is_string($value) && $value !== ''));
    }

    private function resolveLocaleVariant(string $locale): ?string
    {
        $localization = Localization::query()
            ->where('locale_variant', $locale)
            ->where('is_active_frontend', true)
            ->first();

        if ($localization !== null) {
            return $localization->locale_variant;
        }

        return Localization::query()
            ->whereHas('language', fn ($query) => $query->where('alpha2', $locale))
            ->where('is_active_frontend', true)
            ->orderByDesc('is_default')
            ->value('locale_variant');
    }
}
