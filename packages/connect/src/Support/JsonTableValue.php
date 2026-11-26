<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

final class JsonTableValue
{
    public static function stringify(mixed $state): string
    {
        if ($state === null) {
            return '';
        }

        if (is_string($state)) {
            return $state;
        }

        if (is_bool($state)) {
            return $state ? 'true' : 'false';
        }

        if (is_int($state) || is_float($state)) {
            return (string) $state;
        }

        $json = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($json) ? $json : '';
    }
}
