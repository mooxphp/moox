<?php

declare(strict_types=1);

namespace Moox\UserSession\Services;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SessionRelationService
{
    public function associateUserSession(Authenticatable $user): void
    {
        try {
            if (! Schema::hasTable('sessions')) {
                return;
            }

            $sessionId = session()->getId();

            if (blank($sessionId)) {
                return;
            }

            $userId = $user->getAuthIdentifier();
            $userType = $user::class;

            $payload = [
                'user_id' => $userId,
                'last_activity' => now()->getTimestamp(),
            ];

            if (Schema::hasColumn('sessions', 'user_type')) {
                $payload['user_type'] = $userType;
            }

            $updated = DB::table('sessions')->where('id', $sessionId)->update($payload);

            if ($updated === 0) {
                Log::warning('Session not found for ID:', ['session_id' => $sessionId]);
            }
        } catch (Exception $exception) {
            Log::error('Failed to associate user session:', ['error' => $exception->getMessage()]);
        }
    }
}
