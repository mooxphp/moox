<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Value;

/**
 * Internal marker: a hashed password exists in storage.
 */
final class StoredPassword
{
    private function __construct() {}

    public static function instance(): self
    {
        static $instance = null;

        return $instance ??= new self;
    }
}
