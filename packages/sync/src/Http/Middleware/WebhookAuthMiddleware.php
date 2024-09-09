<?php

namespace Moox\Sync\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Moox\Sync\Models\Platform;

class WebhookAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-Platform-Token');
        $signature = $request->header('X-Webhook-Signature');
        $syncToken = config('sync.sync_token');

        $platform = Platform::where('api_token', $token)->first();

        if (! $platform && $token !== $syncToken) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->getContent();
        $calculatedSignature = hash_hmac('sha256', $payload, ($platform ? $platform->api_token : '').$syncToken);

        if (! hash_equals($signature, $calculatedSignature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
