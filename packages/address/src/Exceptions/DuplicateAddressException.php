<?php

declare(strict_types=1);

namespace Moox\Address\Exceptions;

use Illuminate\Validation\ValidationException;

class DuplicateAddressException extends ValidationException
{
    public static function forAddress(): self
    {
        return static::withMessages([
            'street' => [__('address::fields.duplicate_address')],
        ]);
    }
}
