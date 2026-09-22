<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

use Illuminate\Support\Number;

final class HtmlLength
{
    /**
     * @return array{bytes: int, characters: int}
     */
    public static function of(?string $html): array
    {
        if (! is_string($html) || $html === '') {
            return [
                'bytes' => 0,
                'characters' => 0,
            ];
        }

        return [
            'bytes' => strlen($html),
            'characters' => mb_strlen($html, 'UTF-8'),
        ];
    }

    public static function formatBytes(int $bytes): string
    {
        return __('mail-testing::translations.bytes_count', [
            'count' => self::number($bytes),
        ]);
    }

    public static function formatCharacters(int $characters): string
    {
        return __('mail-testing::translations.characters_count', [
            'count' => self::number($characters),
        ]);
    }

    public static function number(int $value): string
    {
        $formatted = Number::format($value, locale: app()->getLocale());

        return $formatted === false ? (string) $value : $formatted;
    }
}
