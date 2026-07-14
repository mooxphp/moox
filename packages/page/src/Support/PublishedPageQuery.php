<?php

declare(strict_types=1);

namespace Moox\Page\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Moox\Page\Models\Page;

final class PublishedPageQuery
{
    /**
     * @return Builder<Page>
     */
    public function base(string $locale, ?array $lookupValues = null): Builder
    {
        return PageModels::page()::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($query) use ($locale, $lookupValues): void {
                $query
                    ->where('locale', $locale)
                    ->where('translation_status', 'published')
                    ->whereNotNull('published_at');

                if ($lookupValues !== null) {
                    $query->where(function ($query) use ($lookupValues): void {
                        $query
                            ->whereIn('slug', $lookupValues)
                            ->orWhereIn('permalink', $lookupValues);
                    });
                }
            })
            ->with(['translations' => fn ($query) => $query->where('locale', $locale)]);
    }

    public function findHomepage(string $locale): ?Page
    {
        $page = $this->base($locale)
            ->homepage()
            ->first();

        return $this->refreshPage($page, $locale);
    }

    public function findBySlug(string $slug, string $locale): ?Page
    {
        $page = $this->base($locale, PagePermalink::lookupCandidates($slug))->first();

        return $this->refreshPage($page, $locale);
    }

    /**
     * @return Collection<int, Page>
     */
    public function all(string $locale): Collection
    {
        return $this->base($locale)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Page>
     */
    public function forLayout(string $locale, string $layout): Collection
    {
        return $this->base($locale)
            ->where('layout', $layout)
            ->orderBy('id')
            ->get()
            ->map(function (Page $page) use ($locale): Page {
                return $this->refreshPage($page, $locale) ?? $page;
            });
    }

    /**
     * @return ($page is null ? null : Page)
     */
    private function refreshPage(?Page $page, string $locale): ?Page
    {
        if ($page === null) {
            return null;
        }

        $page->unsetRelation('translations');
        $page->load(['translations' => fn ($query) => $query->where('locale', $locale)]);

        return $page;
    }
}
