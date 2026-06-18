<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class CauserResolver
{
    public static function resolve(): Model|Authenticatable|null
    {
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
}
