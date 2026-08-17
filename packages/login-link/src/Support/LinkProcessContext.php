<?php

declare(strict_types=1);

namespace Moox\LoginLink\Support;

final class LinkProcessContext
{
    public const AUTH = 'auth';

    public const PUBLIC = 'public';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::AUTH,
            self::PUBLIC,
        ];
    }

    public static function isValid(string $context): bool
    {
        return in_array($context, self::all(), true);
    }
}
