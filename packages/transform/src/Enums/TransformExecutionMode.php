<?php

declare(strict_types=1);

namespace Moox\Transform\Enums;

enum TransformExecutionMode: string
{
    case Single = 'single';
    case Expand = 'expand';
    case Bulk = 'bulk';

    public static function tryFromConfig(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $mode = self::tryFrom($value);
            if ($mode instanceof self) {
                return $mode;
            }
        }

        return self::Single;
    }
}
