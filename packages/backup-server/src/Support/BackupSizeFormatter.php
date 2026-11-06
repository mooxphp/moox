<?php

declare(strict_types=1);

namespace Moox\BackupServerUi\Support;

final class BackupSizeFormatter
{
    private const int KilobytesPerMegabyte = 1024;

    private const int KilobytesPerGigabyte = 1_048_576;

    public static function format(?int $kilobytes): string
    {
        $kilobytes = max(0, $kilobytes ?? 0);

        if ($kilobytes >= self::KilobytesPerGigabyte) {
            return number_format($kilobytes / self::KilobytesPerGigabyte, 2, '.', '').' GB';
        }

        return number_format($kilobytes / self::KilobytesPerMegabyte, 2, '.', '').' MB';
    }
}
