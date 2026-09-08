<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class ReviewNotificationCache
{
    public static function notifiedKey(string $documentId): string
    {
        return 'e-billing.review-notified.'.$documentId;
    }

    public static function forgetNotified(string $documentId): void
    {
        Cache::forget(self::notifiedKey($documentId));
    }

    public static function escalatedKey(string $documentId, string $levelKey): string
    {
        return 'e-billing.review-escalated.'.$documentId.'.'.$levelKey;
    }

    public static function forgetEscalated(string $documentId): void
    {
        foreach (ApprovalEscalation::configuredLevelKeys() as $levelKey) {
            Cache::forget(self::escalatedKey($documentId, $levelKey));
        }
    }

    public static function batchStoreKey(?Carbon $now = null): string
    {
        $now ??= Carbon::now();
        [$mode, $minutes] = self::batchModeAndMinutes();

        if ($mode === 'day') {
            return 'e-billing.review-batch.day.'.$now->format('Y-m-d');
        }

        $bucket = intdiv($now->getTimestamp(), $minutes * 60);

        return 'e-billing.review-batch.window.'.$bucket;
    }

    public static function batchTtlSeconds(?Carbon $now = null): int
    {
        $now ??= Carbon::now();
        [$mode, $minutes] = self::batchModeAndMinutes();

        if ($mode === 'day') {
            return max(3600, (int) ($now->copy()->endOfDay()->addHour()->getTimestamp() - $now->getTimestamp()));
        }

        return $minutes * 60 * 2;
    }

    /**
     * @return array{0: string, 1: int}
     */
    private static function batchModeAndMinutes(): array
    {
        $mode = (string) config('e-billing.notification.batch_key', 'window');
        $minutes = max(1, (int) config('e-billing.notification.batch_window_minutes', 60));

        return [$mode, $minutes];
    }
}
