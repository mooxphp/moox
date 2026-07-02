<?php

declare(strict_types=1);

namespace Moox\FrontendAuth\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Moox\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

class FrontendAuthMiddleware
{
    /**
     * Memoize expensive auth-configuration per (guard|userModel) for the current PHP process.
     *
     * This avoids repeating config writes + Filament panel setup on every request.
     *
     * @var array<string, array{auth: bool, panel: bool}>
     */
    private static array $authConfigurationState = [];

    public function handle(Request $request, Closure $next): Response
    {
        if (! moox_frontend_auth_enabled()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        // Filament has multiple auth routes (login/register/password-reset/etc).
        // If we redirect those back to the login page, we can easily create redirect-loops.
        if (is_string($routeName) && str_contains($routeName, '.auth.')) {
            return $next($request);
        }

        // Filament exports are protected by signed URLs + an auth guard query parameter.
        // Redirecting these requests to the login page breaks the download flow and may
        // send users to the wrong panel (e.g. /admin instead of /wilo).
        if (is_string($routeName) && str_starts_with($routeName, 'filament.exports.')) {
            return $next($request);
        }

        $this->configureAuthFromConfig();

        // Use Filament's auth check so we align with its panel access rules.
        if (Filament::auth()->check()) {
            return $next($request);
        }

        $filamentLoginUrl = $this->getFilamentLoginUrlSafely();
        if ($this->isFilamentLoginRequest($request, $filamentLoginUrl)) {
            return $next($request);
        }

        $redirectAfterLogin = config('moox-frontend-auth.redirect_after_login', '/');
        session()->put('url.intended', $redirectAfterLogin);

        $redirectIfGuest = (string) config('moox-frontend-auth.redirect_if_guest', '/login');

        $targetLoginUrl = $redirectIfGuest;
        if ($redirectIfGuest === '/login' || blank($redirectIfGuest)) {
            if ($filamentLoginUrl !== null) {
                $targetLoginUrl = $filamentLoginUrl;
            }
        }

        return redirect()->to($targetLoginUrl);
    }

    private function getFilamentLoginUrlSafely(): ?string
    {
        try {
            return Filament::getLoginUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    private function configureAuthFromConfig(): void
    {
        $guard = (string) config('moox-frontend-auth.guard', 'web');
        $userModel = (string) config('moox-frontend-auth.user_model', User::class);

        $cacheKey = $guard.'|'.$userModel;
        $state = self::$authConfigurationState[$cacheKey] ?? ['auth' => false, 'panel' => false];

        // Everything already configured for this (guard|userModel) in this PHP process.
        if ($state['auth'] && $state['panel']) {
            return;
        }

        // Ensure Laravel auth can authenticate that user model for the configured guard.
        // Use static config keys to keep static analysis clean.
        if (! $state['auth']) {
            $guards = config('auth.guards', []);
            $providers = config('auth.providers', []);

            if (! is_array($guards[$guard] ?? null)) {
                $guards[$guard] = [
                    'driver' => 'session',
                    'provider' => 'users',
                ];

                config(['auth.guards.'.$guard => $guards[$guard]]);
            }

            $provider = (string) Arr::get($guards[$guard], 'provider', 'users');

            if (! is_array($providers[$provider] ?? null)) {
                $providers[$provider] = [
                    'driver' => 'eloquent',
                    'model' => $userModel,
                ];

                config(['auth.providers.'.$provider => $providers[$provider]]);
            } else {
                $providers[$provider]['model'] = $userModel;
                config(['auth.providers.'.$provider => $providers[$provider]]);
            }

            Auth::shouldUse($guard);

            $state['auth'] = true;
        }

        if (! $state['panel']) {
            try {
                Filament::getDefaultPanel()->authGuard($guard);
                $state['panel'] = true;
            } catch (\Throwable) {
                // If no default panel is available yet, we'll retry on later requests.
            }
        }

        self::$authConfigurationState[$cacheKey] = $state;
    }

    private function isFilamentLoginRequest(Request $request, ?string $filamentLoginUrl): bool
    {
        if ($filamentLoginUrl === null) {
            return false;
        }

        $routeName = $request->route()?->getName();
        // Filament has multiple auth routes (login/register/password-reset/etc).
        // If we redirect those back to the login page, we can easily create redirect-loops.
        if (is_string($routeName) && str_contains($routeName, '.auth.')) {
            return true;
        }

        $loginPath = parse_url($filamentLoginUrl, PHP_URL_PATH) ?: null;
        if ($loginPath === null) {
            return false;
        }

        // Direct navigation to the login page.
        if ($request->path() === ltrim($loginPath, '/')) {
            return true;
        }

        // Livewire auth requests usually POST to `/livewire/*` and may not carry the original route name.
        // The safest signal is the `Referer` header pointing back to the login page.
        if ($this->isLivewireRequest($request)) {
            $referer = (string) $request->header('referer');
            if ($referer !== '') {
                $refererPath = parse_url($referer, PHP_URL_PATH) ?: null;

                if ($refererPath !== null) {
                    if ($refererPath === '/'.ltrim($loginPath, '/')) {
                        return true;
                    }

                    try {
                        $refererRoute = app('router')->getRoutes()->match(Request::create($refererPath, 'GET'));
                        $refererRouteName = $refererRoute->getName();

                        if (is_string($refererRouteName) && str_starts_with($refererRouteName, 'filament.')) {
                            return true;
                        }

                        if (is_string($refererRouteName) && str_contains($refererRouteName, '.auth.')) {
                            return true;
                        }
                    } catch (\Throwable) {
                        // If the referer can't be matched to a route, fall back to default behavior.
                    }
                }
            }
        }

        return false;
    }

    private function isLivewireRequest(Request $request): bool
    {
        return $request->header('X-Livewire') !== null || str_starts_with($request->path(), 'livewire/');
    }
}
