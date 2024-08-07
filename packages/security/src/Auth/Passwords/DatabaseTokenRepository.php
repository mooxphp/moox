<?php

namespace Moox\Security\Auth\Passwords;

use Illuminate\Auth\Passwords\DatabaseTokenRepository as DatabaseTokenRepositoryBase;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class DatabaseTokenRepository extends DatabaseTokenRepositoryBase
{
    /**
     * Create a new token record.
     *
     * @return string
     */
    public function create(CanResetPasswordContract $user)
    {
        $email = $user->getEmailForPasswordReset();
        $userType = $this->getUserType($user);

        $this->deleteSomeExisting($user, $userType);

        $token = $this->createNewToken();

        $payload = $this->createPayload($email, $token, $userType);

        $this->getTable()->insert($payload);

        return $token;
    }

    /**
     * Determine if a token record exists and is valid.
     *
     * @param  string  $token
     * @return bool
     */
    public function exists(CanResetPasswordContract $user, $token)
    {
        $email = $user->getEmailForPasswordReset();
        $userType = $this->getUserType($user);

        $record = $this->getTable()
            ->where('email', $email)
            ->where('user_type', $userType)
            ->first();

        if ($record) {
            $isValid = ! $this->tokenExpired($record->created_at) &&
                $this->hasher->check($token, $record->token);

            return $isValid;
        }

        return false;
    }

    public function delete(CanResetPasswordContract $user)
    {
        $email = $user->getEmailForPasswordReset();
        $userType = $this->getUserType($user);

        $this->getTable()
            ->where('email', $email)
            ->where('user_type', $userType)
            ->delete();

    }

    /**
     * Delete SOME existing reset tokens from the database.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return int
     */
    protected function deleteSomeExisting(string $email, string $userType)
    {
        $limit = 3;

        $records = $this->getTable()
            ->where('email', $email)
            ->where('user_type', $userType)
            ->orderBy('created_at')
            ->get();

        $countToDelete = $records->count() - $limit;

        if ($countToDelete > 0) {
            $idsToDelete = $records->slice(0, $countToDelete)->pluck('id');

            return $this->getTable()
                ->whereIn('id', $idsToDelete)
                ->where('email', $email)
                ->where('user_type', $userType)
                ->delete();
        }

        return 0;
    }

    protected function createPayload(string $email, string $token, string $userType): array
    {
        return [
            'email' => $email,
            'token' => $this->hasher->make($token),
            'user_type' => $userType,
            'created_at' => now(),
        ];
    }

    protected function getUserType(CanResetPasswordContract $user)
    {
        return get_class($user);
    }
}
