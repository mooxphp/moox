<?php

namespace Moox\Firewall\Listeners;

use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class FirewallListener
{
    public function handle(RouteMatched $event)
    {
        $request = $event->request;
        $config = config('firewall');

        if (! ($config['enabled'] ?? false)) {
            return;
        }

        Log::info('🛡️ Moox Firewall listener triggered', [
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
        ]);

        $excludedRoutes = $config['exclude'] ?? [];

        foreach ($excludedRoutes as $pattern) {
            if ($request->is($pattern)) {
                return;
            }
        }

        if (in_array($request->ip(), $config['whitelist'] ?? [])) {
            return;
        }

        if ($request->hasSession() && $request->session()->get('firewall_authenticated', false)) {
            return;
        }

        if (! config('firewall.backdoor')) {
            echo View::make('firewall::access-denied')->render();
            exit;
        }

        $backdoorUrl = $config['backdoor_url'] ?? null;
        $isBackdoorUrl = $backdoorUrl ? ($request->is($backdoorUrl) || $request->path() === ltrim($backdoorUrl, '/')) : false;

        if ($backdoorUrl && ! $isBackdoorUrl) {
            echo View::make('firewall::access-denied')->render();
            exit;
        }

        $token = $config['backdoor_token'] ?? 'let-me-in';
        $requestToken = $request->get('backdoor_token') ?? $request->header('X-Backdoor-Token');

        if ($token && $requestToken === $token) {
            if ($request->hasSession()) {
                $request->session()->put('firewall_authenticated', true);
            }

            if ($isBackdoorUrl) {
                return redirect('/');
            } else {
                return redirect($request->url());
            }
        }

        $errorMessage = null;
        if ($requestToken && $requestToken !== $token) {
            if ($request->hasSession()) {
                $request->session()->put('firewall_error', 'Invalid token. Please try again.');
            } else {
                $errorMessage = 'Invalid request. Please try again.';
            }
        }

        echo View::make('firewall::backdoor', [
            'firewall_error' => $errorMessage,
        ])->render();
        exit;
    }
}
