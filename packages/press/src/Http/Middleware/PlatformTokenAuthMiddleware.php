<?php

namespace Moox\Press\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PlatformTokenAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');
        // if (!$token || !config('press.api.api.model')::where('api_token', $token)->exists()) {
        if (!$token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
