<?php

declare(strict_types=1);

namespace Moox\Migrate\Support;

final class MigrateOutput
{
    public const MAX_LINES = 30;

    public const MAX_CHARS = 1500;

    public const TRUNCATED_MARKER = '… truncated';

    public static function forNotification(?string $output): ?string
    {
        $clean = self::stripAnsi(trim((string) $output));

        if ($clean === '') {
            return null;
        }

        $lines = preg_split('/\R/u', $clean) ?: [];
        $truncatedByLines = count($lines) > self::MAX_LINES;

        if ($truncatedByLines) {
            $clean = implode("\n", array_slice($lines, -self::MAX_LINES));
        }

        $truncatedByChars = mb_strlen($clean) > self::MAX_CHARS;

        if ($truncatedByChars) {
            $clean = ltrim(mb_substr($clean, -self::MAX_CHARS));
        }

        if ($truncatedByLines || $truncatedByChars) {
            return $clean."\n".self::TRUNCATED_MARKER;
        }

        return $clean;
    }

    public static function stripAnsi(string $value): string
    {
        $stripped = preg_replace('/\e\[[\d;?]*[A-Za-z]|\x1B\[[\d;?]*[A-Za-z]/', '', $value);

        return is_string($stripped) ? $stripped : $value;
    }
}
