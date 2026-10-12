<?php

declare(strict_types=1);

namespace Moox\Migrate\Support;

use Illuminate\Support\Facades\Cache;

final class MigrateRunStatus
{
    public const CACHE_KEY = 'moox-migrate-status';

    public const TTL_SECONDS = 600;

    public const STATE_QUEUED = 'queued';

    public const STATE_RUNNING = 'running';

    public const STATE_DONE = 'done';

    public const STATE_FAILED = 'failed';

    public const STATE_BUSY = 'busy';

    /**
     * @param  array{
     *     state: string,
     *     title: string,
     *     body: ?string,
     *     exit_code: ?int,
     *     pending_count: int,
     *     user_id: int|string|null,
     *     user_type: ?string
     * }  $payload
     */
    public static function put(array $payload): void
    {
        Cache::put(self::CACHE_KEY, $payload, self::TTL_SECONDS);
    }

    /**
     * @return array{
     *     state: string,
     *     title: string,
     *     body: ?string,
     *     exit_code: ?int,
     *     pending_count: int,
     *     user_id: int|string|null,
     *     user_type: ?string
     * }|null
     */
    public static function get(): ?array
    {
        $value = Cache::get(self::CACHE_KEY);

        return is_array($value) ? $value : null;
    }

    public static function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function isActive(): bool
    {
        $state = self::get()['state'] ?? null;

        return in_array($state, [self::STATE_QUEUED, self::STATE_RUNNING], true);
    }

    /**
     * @return array{
     *     state: string,
     *     title: string,
     *     body: ?string,
     *     exit_code: ?int,
     *     pending_count: int,
     *     user_id: int|string|null,
     *     user_type: ?string
     * }|null
     */
    public static function consumeTerminal(): ?array
    {
        $status = self::get();

        if ($status === null) {
            return null;
        }

        if (! in_array($status['state'], [self::STATE_DONE, self::STATE_FAILED, self::STATE_BUSY], true)) {
            return null;
        }

        self::clear();

        return $status;
    }
}
