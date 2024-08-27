<?php

namespace Moox\UserSession\Services;

use Moox\UserSession\Models\UserSession;

class SessionRelationService
{
    public function associateUserSession($user): void
    {
        try {
            $sessionId = session()->getId();
            $userType = get_class($user);

            $userSession = UserSession::find($sessionId);

            if ($userSession) {
                $userSession->update([
                    'user_type' => $userType,
                    'user_id' => $user->id,
                    'last_activity' => now()->getTimestamp(),
                ]);
            } else {
                \Log::warning('Session not found for ID:', ['session_id' => $sessionId]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to associate user session:', ['error' => $e->getMessage()]);
        }
    }
}
