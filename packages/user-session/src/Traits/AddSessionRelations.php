<?php

namespace Moox\UserSession\Traits;

use Moox\UserSession\Models\UserSession;

trait AddSessionRelations
{
    // TODO: Test Login for Moox and Press
    // TODO: When redirecting to WordPress, the user_id is not set
    // TODO: When logged in with /press, /press/login should not redirect to moox/login
    // TODO: Whether all in Security or Class Exists
    // TODO: When model is missing or does not exist, sessions should work but show the problem
    protected function associateUserSession($user): void
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
