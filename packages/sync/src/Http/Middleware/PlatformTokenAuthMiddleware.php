<?php

namespace Moox\Sync\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Moox\Sync\Models\Platform;

class PlatformTokenAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');

        if (! $token || ! Platform::where('api_token', $token) === $token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
