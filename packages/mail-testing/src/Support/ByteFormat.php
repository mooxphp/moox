<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

use Illuminate\Support\Number;

final class ByteFormat
{
    public static function bytes(int $bytes): string
    {
        $formatted = Number::fileSize($bytes);

        return is_string($formatted) && $formatted !== '' ? $formatted : $bytes.' B';
    }
}
