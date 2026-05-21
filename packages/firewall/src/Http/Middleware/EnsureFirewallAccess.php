<?php

namespace Moox\Firewall\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Moox\Firewall\Models\FirewallWhitelistEntry;
use Symfony\Component\HttpFoundation\Response;

class EnsureFirewallAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->get('firewall_already_processed') === true) {
            return $next($request);
        }

        $request->attributes->set('firewall_already_processed', true);

        $config = config('firewall');

        if (! ($config['enabled'] ?? false)) {
            return $next($request);
        }

        if ($this->isFrameworkInternalRequest($request)) {
            return $next($request);
        }

        $backdoorPath = trim((string) config('firewall.backdoor_url', $config['backdoor_url'] ?? '/backdoor'), '/');
        $isBackdoorPath = trim($request->path(), '/') === $backdoorPath;

        // Protect only configured routes (optional).
        $protectPatterns = array_values((array) ($config['protect'] ?? []));
        if (! $isBackdoorPath && $protectPatterns !== []) {
            if (! $this->matchesAny($request, $protectPatterns)) {
                return $next($request);
            }
        }

        if ($this->isExcluded($request, (array) ($config['exclude'] ?? []))) {
            return $next($request);
        }

        if (! $isBackdoorPath && $this->isIpAllowedForRequest((string) $request->ip(), $request, $config)) {
            return $next($request);
        }

        if ($this->hasValidFirewallSession($request, (int) ($config['session_ttl_minutes'] ?? 120))) {
            return $next($request);
        }

        $backdoorEnabled = (bool) config('firewall.backdoor', $config['backdoor'] ?? false);
        if (! $backdoorEnabled) {
            return response()->view('firewall::access-denied', [], 403);
        }

        $inlineChallenge = (bool) config('firewall.inline_challenge', $config['inline_challenge'] ?? true);
        // $isBackdoorPath is computed above.

        if (! $isBackdoorPath) {
            if ($request->hasSession() && $this->shouldStoreIntendedUrl($request)) {
                // Store a relative URL to avoid open-redirect issues if the Host header is manipulated.
                $request->session()->put('firewall.intended_url', $this->getIntendedRelativeUrl($request));
            }

            if ($inlineChallenge) {
                // UX: show the token form inline instead of redirecting.
                return response()->view('firewall::backdoor');
            }

            $backdoorUrl = '/'.ltrim((string) ($config['backdoor_url'] ?? '/backdoor'), '/');

            return redirect($backdoorUrl);
        }

        if (! $request->isMethod('post')) {
            return response()->view('firewall::backdoor');
        }

        $token = (string) ($config['backdoor_token'] ?? '');
        $submittedToken = (string) $request->input('backdoor_token', '');

        $rateLimitKey = sprintf('firewall:backdoor:%s', (string) $request->ip());
        $maxAttempts = (int) ($config['backdoor_rate_limit'] ?? 5);
        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            return response()->view('firewall::backdoor', [
                'firewall_error' => __('firewall::translations.error_too_many_attempts'),
            ], 429);
        }

        if ($token === '' || ! hash_equals($token, $submittedToken)) {
            RateLimiter::hit($rateLimitKey, 60);

            return response()->view('firewall::backdoor', [
                'firewall_error' => __('firewall::translations.error_invalid_token'),
            ], 403);
        }

        RateLimiter::clear($rateLimitKey);
        if ($request->hasSession()) {
            $request->session()->regenerate();
            $request->session()->put('firewall_authenticated_at', now()->timestamp);
        }

        $intended = $request->hasSession() ? $request->session()->pull('firewall.intended_url') : null;
        if ($this->isUnsafeIntendedUrl($intended)) {
            $intended = null;
        }

        return redirect($intended ?: '/');
    }

    /**
     * @param  array<int, string>  $patterns
     */
    private function isExcluded(Request $request, array $patterns): bool
    {
        return $this->matchesAny($request, $patterns);
    }

    /**
     * @param  array<int, string>  $patterns
     */
    private function matchesAny(Request $request, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $normalizedPattern = ltrim(trim((string) $pattern), '/');

            if ($normalizedPattern === '') {
                continue;
            }

            if ($request->is($normalizedPattern)) {
                return true;
            }
        }

        return false;
    }

    private function getIntendedRelativeUrl(Request $request): string
    {
        $path = '/'.ltrim((string) $request->path(), '/');
        $query = (string) $request->getQueryString();

        return $query !== '' ? "{$path}?{$query}" : $path;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function isIpAllowedForRequest(string $ip, Request $request, array $config): bool
    {
        $configuredIps = array_values((array) ($config['whitelist'] ?? []));

        if (in_array($ip, $configuredIps, true)) {
            $whitelistAllowPatterns = array_values((array) ($config['whitelist_allow'] ?? []));

            return $whitelistAllowPatterns === [] || $this->matchesAny($request, $whitelistAllowPatterns);
        }

        if (! class_exists(FirewallWhitelistEntry::class)) {
            return false;
        }

        if (! Schema::hasTable('firewall_whitelist_entries')) {
            return false;
        }

        $entry = FirewallWhitelistEntry::query()
            ->where('is_active', true)
            ->where('ip_address', $ip)
            ->first();

        if (! $entry) {
            return false;
        }

        if ((bool) $entry->allow_all_routes) {
            return true;
        }

        $allowedRoutes = is_array($entry->allowed_routes) ? $entry->allowed_routes : [];
        if ($allowedRoutes === []) {
            return false;
        }

        return $this->matchesAny($request, $allowedRoutes);
    }

    private function hasValidFirewallSession(Request $request, int $ttlMinutes): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $authenticatedAt = (int) $request->session()->get('firewall_authenticated_at', 0);

        if ($authenticatedAt <= 0) {
            return false;
        }

        return now()->timestamp <= ($authenticatedAt + ($ttlMinutes * 60));
    }

    private function isFrameworkInternalRequest(Request $request): bool
    {
        $path = trim($request->path(), '/');

        if (str_starts_with($path, 'livewire/') || str_starts_with($path, 'livewire-')) {
            return true;
        }

        return $request->headers->has('X-Livewire');
    }

    private function shouldStoreIntendedUrl(Request $request): bool
    {
        return $request->isMethod('get')
            && ! $request->expectsJson()
            && ! $request->isXmlHttpRequest();
    }

    private function isUnsafeIntendedUrl(?string $url): bool
    {
        if (! $url) {
            return false;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        return str_starts_with(trim($path, '/'), 'livewire-')
            || str_starts_with(trim($path, '/'), 'livewire/');
    }
}
