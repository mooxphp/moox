<?php

declare(strict_types=1);

namespace Moox\Zugferd\Support;

use horstoeko\zugferd\codelists\ZugferdTextSubjectCodeQualifiers;
use ReflectionClass;

/**
 * BT-21 note subject codes: UNTDID 4451 as shipped by horstoeko/zugferd. A code outside it fails
 * KoSIT with BR-CL-08.
 */
final class NoteSubjectCodes
{
    /** @var array<string, true>|null */
    private static ?array $codes = null;

    public static function isValid(string $code): bool
    {
        self::$codes ??= array_fill_keys(
            array_map(strval(...), (new ReflectionClass(ZugferdTextSubjectCodeQualifiers::class))->getConstants()),
            true,
        );

        return isset(self::$codes[$code]);
    }
}
