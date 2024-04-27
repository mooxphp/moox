<?php

namespace Moox\UserSession\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Moox\UserSession\Models\UserSession;

class StoreRelationsInSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (Auth::check()) {
            $user = Auth::user();
            $sessionId = session()->getId();
            $userType = get_class($user);

            UserSession::updateOrCreate(
                [
                    'id' => $sessionId,
                ],
                [
                    'user_type' => $userType,
                    'user_id' => $user->id,
                    'payload' => json_encode([]),
                    'last_activity' => now()->getTimestamp(),
                ]
            );
        }

        return $response;
    }
}
