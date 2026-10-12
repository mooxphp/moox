<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use STS\FilamentImpersonate\Facades\Impersonation;
use Throwable;

final class CauserResolver
{
    public static function resolve(): Model|Authenticatable|null
    {
        $impersonator = self::impersonator();

        if ($impersonator instanceof Model || $impersonator instanceof Authenticatable) {
            return $impersonator;
        }

        $user = auth()->user();

        if ($user instanceof Model || $user instanceof Authenticatable) {
            return $user;
        }

        $systemCauser = config('audit.system_causer');

        if (is_string($systemCauser) && class_exists($systemCauser)) {
            return $systemCauser::query()->first();
        }

        return null;
    }

    /**
     * @return array{impersonated_type: class-string, impersonated_id: int|string}|array{}
     */
    public static function impersonationProperties(): array
    {
        if (! self::isImpersonating()) {
            return [];
        }

        $actedAs = self::impersonatedUser();

        if (! $actedAs instanceof Authenticatable) {
            return [];
        }

        return [
            'impersonated_type' => $actedAs::class,
            'impersonated_id' => $actedAs->getAuthIdentifier(),
        ];
    }

    protected static function isImpersonating(): bool
    {
        if (! class_exists(Impersonation::class)) {
            return false;
        }

        try {
            return (bool) Impersonation::isImpersonating();
        } catch (Throwable) {
            return false;
        }
    }

    protected static function impersonator(): ?Authenticatable
    {
        if (! self::isImpersonating()) {
            return null;
        }

        try {
            $impersonator = Impersonation::getImpersonator();
        } catch (Throwable) {
            return null;
        }

        return $impersonator instanceof Authenticatable ? $impersonator : null;
    }

    protected static function impersonatedUser(): ?Authenticatable
    {
        if (! class_exists(Impersonation::class)) {
            return null;
        }

        try {
            $guard = Impersonation::getImpersonatorGuardUsingName();
        } catch (Throwable) {
            return null;
        }

        if (! is_string($guard) || $guard === '') {
            $user = auth()->user();

            return $user instanceof Authenticatable ? $user : null;
        }

        $user = auth()->guard($guard)->user();

        return $user instanceof Authenticatable ? $user : null;
    }
}
