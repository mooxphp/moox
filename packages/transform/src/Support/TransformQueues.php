<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

final class TransformQueues
{
    public static function dispatch(): string
    {
        return (string) config('transform.dispatch_queue', 'transform');
    }

    public static function run(): string
    {
        return (string) config('transform.job_queue', 'transform');
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_unique([self::dispatch(), self::run()]));
    }
}
