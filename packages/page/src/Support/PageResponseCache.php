<?php

declare(strict_types=1);

namespace Moox\Page\Support;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class PageResponseCache
{
    private const string VERSION_KEY = 'page.cache.version';

    /**
     * @param  Closure(): (View|Response)  $resolver
     */
    public function remember(string $locale, string $slug, Closure $resolver): View|Response
    {
        if (! $this->shouldCache()) {
            return $resolver();
        }

        $cacheKey = $this->cacheKey($locale, $slug);
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return response($cached);
        }

        $result = $resolver();
        $html = $this->toHtml($result);

        $ttl = (int) config('page.cache.ttl', 3600);
        Cache::put($cacheKey, $html, $ttl);

        return $result;
    }

    public function forgetAll(): void
    {
        Cache::forget('page.localizations.frontend');
        $this->bumpVersion();
    }

    public function bumpVersion(): void
    {
        if (! Cache::has(self::VERSION_KEY)) {
            Cache::forever(self::VERSION_KEY, 1);

            return;
        }

        Cache::increment(self::VERSION_KEY);
    }

    private function shouldCache(): bool
    {
        if (! config('page.cache.enabled', false)) {
            return false;
        }

        if (! request()->isMethod('GET')) {
            return false;
        }

        if (request()->has('lang')) {
            return false;
        }

        if (Auth::check()) {
            return false;
        }

        return true;
    }

    private function cacheKey(string $locale, string $slug): string
    {
        $version = (string) Cache::get(self::VERSION_KEY, 0);

        return 'page.response.'.md5($locale.'|'.$slug.'|'.$version);
    }

    private function toHtml(View|Response $result): string
    {
        if ($result instanceof Response) {
            return $result->getContent() ?: '';
        }

        return $result->render();
    }
}
