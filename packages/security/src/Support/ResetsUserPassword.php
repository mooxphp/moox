<?php

declare(strict_types=1);

namespace Moox\Security\Support;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Moox\Security\Helper\PasswordHash;

trait ResetsUserPassword
{
    use ResolvesAuthPasswordContext;

    protected function resetUserPassword(CanResetPassword|Model|Authenticatable $user, string $plainPassword): void
    {
        if ($this->modelUsesWordPressPassword($user)) {
            $passwordHash = new PasswordHash(8, true);
            $user->forceFill([
                'user_pass' => $passwordHash->HashPassword($plainPassword),
                'remember_token' => Str::random(60),
            ])->save();
        } else {
            $user->forceFill([
                'password' => Hash::make($plainPassword),
                'remember_token' => Str::random(60),
            ])->save();
        }

        event(new PasswordReset($user));
    }
}
