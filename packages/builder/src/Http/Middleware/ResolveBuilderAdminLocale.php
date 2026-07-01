<?php

declare(strict_types=1);

namespace Moox\Builder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Localization\Models\Localization;
use Symfony\Component\HttpFoundation\Response;

final class ResolveBuilderAdminLocale
{
    public function __construct(
        protected BuilderLocaleResolver $localeResolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldResolveLocale($request)) {
            return $next($request);
        }

        $locale = $request->query('lang') ?? $request->input('lang');

        if (! is_string($locale) || $locale === '') {
            $sessionLocale = session(BuilderLocaleResolver::ADMIN_SESSION_KEY);

            if (is_string($sessionLocale) && $sessionLocale !== '') {
                $request->merge(['lang' => $sessionLocale]);
            }

            return $next($request);
        }

        if (! $this->isAllowedAdminLocale($locale)) {
            $locale = $this->localeResolver->adminDefaultLocale();

            session([BuilderLocaleResolver::ADMIN_SESSION_KEY => $locale]);
            $request->merge(['lang' => $locale]);

            if ($request->query('lang') !== null) {
                return redirect()->to($request->url().'?'.http_build_query([
                    ...$request->query(),
                    'lang' => $locale,
                ]));
            }

            return $next($request);
        }

        session([BuilderLocaleResolver::ADMIN_SESSION_KEY => $locale]);
        $request->merge(['lang' => $locale]);

        return $next($request);
    }

    protected function isAllowedAdminLocale(string $locale): bool
    {
        if (! class_exists(Localization::class) || ! Schema::hasTable('localizations')) {
            return true;
        }

        return Localization::query()
            ->where('locale_variant', $locale)
            ->where('is_active_admin', true)
            ->exists();
    }

    protected function shouldResolveLocale(Request $request): bool
    {
        if ($request->is('livewire/*')) {
            return true;
        }

        if ($request->query('lang') !== null || $request->input('lang') !== null) {
            return true;
        }

        if (session(BuilderLocaleResolver::ADMIN_SESSION_KEY) !== null) {
            return true;
        }

        return $request->is('admin/*') || $request->is('filament/*');
    }
}
