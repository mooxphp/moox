<?php

declare(strict_types=1);

namespace Moox\Definition\Laravel;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<DateTimeImmutable|null, DateTimeInterface>
 */
final class UtcDateTimeCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        return new DateTimeImmutable((string) $value, new DateTimeZone('UTC'));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return DateTimeImmutable::createFromInterface($value)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
    }
}
