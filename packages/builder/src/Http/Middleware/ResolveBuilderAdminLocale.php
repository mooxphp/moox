<?php

declare(strict_types=1);

namespace Moox\Builder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Support\BuilderAdminLocalizationCatalog;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Support\CustomFieldsTranslatability;
use Symfony\Component\HttpFoundation\Response;

final class ResolveBuilderAdminLocale
{
    private const FIELD_GROUP_ROUTE_SLUG = 'field-groups';

    public function __construct(
        protected BuilderLocaleResolver $localeResolver,
        protected BuilderAdminLocalizationCatalog $localizationCatalog,
        protected EntityRegistry $entityRegistry,
        protected CustomFieldsTranslatability $translatability,
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
        return $this->localizationCatalog->isAllowedAdminLocale($locale);
    }

    protected function shouldResolveLocale(Request $request): bool
    {
        if ($request->query('lang') !== null || $request->input('lang') !== null) {
            return $this->targetsBuilderLocaleContext($request);
        }

        if ($request->is('livewire/*')) {
            return session(BuilderLocaleResolver::ADMIN_SESSION_KEY) !== null
                && $this->livewireTargetsBuilderLocaleContext($request);
        }

        if (session(BuilderLocaleResolver::ADMIN_SESSION_KEY) !== null) {
            return $this->targetsBuilderLocaleContext($request);
        }

        return $this->targetsBuilderLocaleContext($request);
    }

    protected function targetsBuilderLocaleContext(Request $request): bool
    {
        if ($this->isFieldGroupAdminRequest($request)) {
            return true;
        }

        return $this->isTranslatableCustomFieldsRequest($request);
    }

    protected function livewireTargetsBuilderLocaleContext(Request $request): bool
    {
        $referer = (string) $request->headers->get('referer', '');

        if ($referer === '') {
            return false;
        }

        $refererPath = (string) parse_url($referer, PHP_URL_PATH);

        return $this->pathTargetsBuilderLocaleContext(trim($refererPath, '/'));
    }

    protected function isFieldGroupAdminRequest(Request $request): bool
    {
        return $this->pathTargetsFieldGroupAdmin(trim($request->path(), '/'));
    }

    protected function isTranslatableCustomFieldsRequest(Request $request): bool
    {
        return $this->pathTargetsTranslatableCustomFields(trim($request->path(), '/'));
    }

    protected function pathTargetsBuilderLocaleContext(string $path): bool
    {
        return $this->pathTargetsFieldGroupAdmin($path)
            || $this->pathTargetsTranslatableCustomFields($path);
    }

    protected function pathTargetsFieldGroupAdmin(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        $slug = self::FIELD_GROUP_ROUTE_SLUG;

        return $slug !== '' && str_contains($path, $slug);
    }

    protected function pathTargetsTranslatableCustomFields(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        foreach ($this->entityRegistry->all() as $definition) {
            $resourceClass = $definition['resource'] ?? null;

            if (! is_string($resourceClass) || ! $this->translatability->forResource($resourceClass)) {
                continue;
            }

            if (! method_exists($resourceClass, 'getSlug')) {
                continue;
            }

            $slug = $resourceClass::getSlug();

            if ($slug !== '' && str_contains($path, $slug)) {
                return true;
            }
        }

        return false;
    }
}
