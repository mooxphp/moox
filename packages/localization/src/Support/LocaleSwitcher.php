<?php

declare(strict_types=1);

namespace Moox\Localization\Support;

use Illuminate\Support\Collection;
use Moox\Localization\Models\Localization;

final class LocaleSwitcher
{
    /**
     * @return Collection<int, string>
     */
    public static function allowedLanguageCodes(string $context): Collection
    {
        return Localization::query()
            ->with('language')
            ->when($context === 'backend', fn ($query) => $query->where('is_active_admin', true))
            ->when($context === 'frontend', fn ($query) => $query->where('is_active_frontend', true))
            ->get()
            ->map(fn (Localization $localization): ?string => $localization->language?->alpha2)
            ->filter(fn (?string $code): bool => is_string($code) && $code !== '')
            ->unique()
            ->values();
    }

    public static function isAllowedLanguageCode(string $locale, string $context): bool
    {
        return self::allowedLanguageCodes($context)->contains($locale);
    }

    /**
     * Only same-host absolute URLs or same-origin relative paths.
     */
    public static function safeRedirectUrl(?string $url, ?string $fallback = '/'): string
    {
        $fallback = $fallback !== null && $fallback !== '' ? $fallback : '/';

        if (! is_string($url) || $url === '') {
            return $fallback;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return $fallback;
        }

        if (! isset($parts['host'])) {
            $path = $parts['path'] ?? '/';

            if ($path === '' || str_starts_with($path, '//') || str_contains($path, '\\')) {
                return $fallback;
            }

            if (! str_starts_with($path, '/')) {
                return $fallback;
            }

            $query = isset($parts['query']) ? '?'.$parts['query'] : '';

            return $path.$query;
        }

        $candidateHost = strtolower((string) $parts['host']);
        $allowedHosts = self::allowedRedirectHosts();

        if (! in_array($candidateHost, $allowedHosts, true)) {
            return $fallback;
        }

        $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : null;

        if ($scheme !== null && ! in_array($scheme, ['http', 'https'], true)) {
            return $fallback;
        }

        return $url;
    }

    /**
     * @return list<string>
     */
    private static function allowedRedirectHosts(): array
    {
        $hosts = [];

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($appHost) && $appHost !== '') {
            $hosts[] = strtolower($appHost);
        }

        try {
            $requestHost = request()->getHost();
            if (is_string($requestHost) && $requestHost !== '') {
                $hosts[] = strtolower($requestHost);
            }
        } catch (\Throwable) {
            // No request bound (e.g. unit tests).
        }

        return array_values(array_unique($hosts));
    }
}
