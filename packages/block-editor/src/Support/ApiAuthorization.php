<?php

namespace Moox\BlockEditor\Support;

class ApiAuthorization
{
    public static function isEnabled(): bool
    {
        $configured = config('moox-editor.api.authorization');
        if (is_bool($configured)) {
            return $configured;
        }

        $middleware = config('moox-editor.api.middleware', ['web', 'auth', 'throttle:60,1']);
        if (! is_array($middleware)) {
            return false;
        }

        $normalized = array_values(array_filter(
            $middleware,
            static fn (mixed $entry): bool => is_string($entry) && trim($entry) !== ''
        ));

        return count($normalized) > 0;
    }
}
