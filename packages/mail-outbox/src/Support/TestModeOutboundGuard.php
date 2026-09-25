<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

final class TestModeOutboundGuard
{
    private static int $depth = 0;

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function whileHandling(callable $callback): mixed
    {
        self::$depth++;

        try {
            return $callback();
        } finally {
            self::$depth--;
        }
    }

    public static function isHandling(): bool
    {
        return self::$depth > 0;
    }
}
