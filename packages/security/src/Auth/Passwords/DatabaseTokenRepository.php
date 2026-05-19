<?php

namespace Moox\Security\Auth\Passwords;

use Illuminate\Auth\Passwords\DatabaseTokenRepository as DatabaseTokenRepositoryBase;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Support\Facades\Schema;
use Override;

class DatabaseTokenRepository extends DatabaseTokenRepositoryBase
{
    /**
     * Create a new token record.
     *
     * @return string
     */
    #[Override]
    public function create(CanResetPasswordContract $user)
    {
        $email = $user->getEmailForPasswordReset();
        $userType = $this->getUserType($user);

        $this->deleteExistingForEmail($email);

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
    #[Override]
    public function exists(CanResetPasswordContract $user, $token)
    {
        $email = $user->getEmailForPasswordReset();

        // Primary key is `email` (Laravel default) — one token per address.
        $record = $this->getTable()
            ->where('email', $email)
            ->first();

        if ($record) {
            return ! $this->tokenExpired($record->created_at) &&
                $this->hasher->check($token, $record->token);
        }

        return false;
    }

    #[Override]
    public function delete(CanResetPasswordContract $user): void
    {
        $this->deleteExistingForEmail($user->getEmailForPasswordReset());
    }

    /**
     * Delete existing reset tokens for the email address.
     *
     * The table primary key is `email` only; `user_type` is stored for auditing only.
     *
     * @return int
     */
    protected function deleteExistingForEmail(string $email): int
    {
        return $this->getTable()->where('email', $email)->delete();
    }

    protected function createPayload(string $email, string $token, string $userType): array
    {
        $payload = [
            'email' => $email,
            'token' => $this->hasher->make($token),
            'created_at' => now(),
        ];

        if ($this->supportsUserTypes()) {
            $payload['user_type'] = $userType;
        }

        return $payload;
    }

    protected function getUserType(CanResetPasswordContract $user): string
    {
        return $user::class;
    }

    protected function supportsUserTypes(): bool
    {
        return Schema::hasColumn($this->table, 'user_type');
    }
}
