<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

final class NormalizedHtml
{
    public static function normalize(string $html): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', $html);

        return trim(is_string($collapsed) ? $collapsed : $html);
    }

    public static function forDisplay(string $html): string
    {
        $normalizedNewlines = str_replace(["\r\n", "\r"], "\n", $html);

        if (str_contains($normalizedNewlines, "\n")) {
            return rtrim($normalizedNewlines);
        }

        $pretty = preg_replace('/>\s*</u', ">\n<", $normalizedNewlines);

        return is_string($pretty) ? $pretty : $normalizedNewlines;
    }

    public static function hash(string $html): string
    {
        return hash('sha1', self::normalize($html));
    }
}
