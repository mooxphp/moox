<?php

declare(strict_types=1);

namespace Moox\UserSession\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\UserSession\Services\SessionRelationService;
use Symfony\Component\HttpFoundation\Response;

class SyncUserRelationToSessionRow
{
    public function __construct(protected SessionRelationService $sessionRelationService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = filament()->auth()->user() ?? Auth::user();

        if (! $user) {
            return $response;
        }

        if (! Schema::hasTable('sessions') || ! Schema::hasColumn('sessions', 'user_type')) {
            return $response;
        }

        $sessionId = session()->getId();

        if (blank($sessionId)) {
            return $response;
        }

        $row = DB::table('sessions')
            ->where('id', $sessionId)
            ->first(['user_id', 'user_type']);

        if (
            $row
            && (string) $row->user_id === (string) $user->getAuthIdentifier()
            && $row->user_type === $user::class
        ) {
            return $response;
        }

        $this->sessionRelationService->associateUserSession($user);

        return $response;
    }
}
