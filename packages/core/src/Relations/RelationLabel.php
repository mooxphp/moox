<?php

declare(strict_types=1);

namespace Moox\Core\Relations;

final class RelationLabel
{
    public static function resolve(?string $label, string $fallback): string
    {
        if ($label === null || $label === '') {
            return $fallback;
        }

        if (str_starts_with($label, 'trans//')) {
            $key = substr($label, 7);
            $translated = trans($key);

            return $translated !== $key ? $translated : $fallback;
        }

        return $label;
    }
}
