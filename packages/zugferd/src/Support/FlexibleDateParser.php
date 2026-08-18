<?php

declare(strict_types=1);

namespace Moox\Zugferd\Support;

final class FlexibleDateParser
{
    /** @var list<string> */
    private const FORMATS = ['Y-m-d', 'd.m.Y', 'd.m.y'];

    public static function parse(?string $value): ?\DateTimeInterface
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        foreach (self::FORMATS as $format) {
            $parsed = \DateTimeImmutable::createFromFormat('!'.$format, $trimmed);
            if ($parsed instanceof \DateTimeImmutable) {
                return $parsed;
            }
        }

        return null;
    }
}
