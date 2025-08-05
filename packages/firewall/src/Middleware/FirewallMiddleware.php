<?php

declare(strict_types=1);

namespace Moox\Firewall\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class FirewallMiddleware
{
    public function handle(Request $request, Closure $next): Response|SymfonyResponse|RedirectResponse
    {
        $config = config('firewall');

        if (! ($config['global_enabled'] ?? false)) {
            return $next($request);
        }

        if ($this->isWhitelisted($request, $config)) {
            return $next($request);
        }

        if ($this->hasValidSession($request)) {
            return $next($request);
        }

        if ($this->hasValidBackdoor($request, $config)) {
            $this->setFirewallSession($request);

            return $this->redirectWithoutToken($request);
        }

        if ($this->hasInvalidToken($request, $config)) {
            return $this->showFirewallPage($request, true);
        }

        if ($this->shouldShowFirewallPage($request, $config)) {
            return $this->showFirewallPage($request, false);
        }

        return response('Access denied', 403);
    }

    private function isWhitelisted(Request $request, array $config): bool
    {
        $clientIp = $request->ip();
        $whitelist = $config['whitelist'] ?? [];

        return in_array($clientIp, $whitelist, true);
    }

    private function hasValidBackdoor(Request $request, array $config): bool
    {
        if (! ($config['backdoor'] ?? false)) {
            return false;
        }

        $token = $config['backdoor_token'] ?? '';
        $requestToken = $request->get('backdoor_token') ?? $request->header('X-Backdoor-Token');

        return $token && $requestToken === $token;
    }

    private function hasValidSession(Request $request): bool
    {
        return $request->session()->has('firewall_authenticated');
    }

    private function setFirewallSession(Request $request): void
    {
        $request->session()->put('firewall_authenticated', true);
    }

    private function redirectWithoutToken(Request $request): RedirectResponse
    {
        $url = $request->url();
        $query = $request->query();
        unset($query['backdoor_token']);

        if (! empty($query)) {
            $url .= '?'.http_build_query($query);
        }

        return redirect($url);
    }

    private function hasInvalidToken(Request $request, array $config): bool
    {
        if (! ($config['backdoor'] ?? false)) {
            return false;
        }

        $token = $config['backdoor_token'] ?? '';
        $requestToken = $request->get('backdoor_token') ?? $request->header('X-Backdoor-Token');

        return $requestToken && $requestToken !== $token && $request->isMethod('GET');
    }

    private function showFirewallPage(Request $request, bool $hasError): Response
    {
        if ($hasError) {
            $request->session()->flash('firewall_error', 'Invalid token. Please try again.');
        }

        return response()->view('firewall::firewall');
    }

    private function shouldShowFirewallPage(Request $request, array $config): bool
    {
        if (! ($config['backdoor'] ?? false)) {
            return false;
        }

        $token = $config['backdoor_token'] ?? '';
        if (! $token) {
            return false;
        }

        $requestToken = $request->get('backdoor_token') ?? $request->header('X-Backdoor-Token');

        return ! $requestToken && $request->isMethod('GET');
    }
}
