<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

use Illuminate\Support\Number;

final class DurationFormat
{
    public static function milliseconds(int $milliseconds): string
    {
        if ($milliseconds < 1000) {
            return $milliseconds.' ms';
        }

        if ($milliseconds < 60_000) {
            $formatted = Number::format(
                $milliseconds / 1000,
                maxPrecision: 2,
                locale: app()->getLocale(),
            );

            return ($formatted === false ? (string) round($milliseconds / 1000, 2) : $formatted).' s';
        }

        $minutes = intdiv($milliseconds, 60_000);
        $seconds = (int) round(($milliseconds % 60_000) / 1000);

        if ($seconds === 60) {
            $minutes++;
            $seconds = 0;
        }

        if ($seconds === 0) {
            return $minutes.' min';
        }

        return $minutes.' min '.$seconds.' s';
    }
}
