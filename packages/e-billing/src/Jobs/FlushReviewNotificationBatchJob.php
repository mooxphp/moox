<?php

declare(strict_types=1);

namespace Moox\EBilling\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Moox\EBilling\Support\ReviewNotificationCache;
use Moox\EBilling\Support\ReviewNotificationDispatcher;
use Moox\Jobs\Traits\JobProgress;
use Throwable;

/**
 * Drains the batched review-notification cache store and dispatches one
 * {@see NotifyDocumentsNeedReviewJob} with all collected documents via
 * {@see ReviewNotificationDispatcher}.
 */
final class FlushReviewNotificationBatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use JobProgress;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 2;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300];

    public function handle(): void
    {
        $this->setProgress(0);

        $key = ReviewNotificationCache::batchStoreKey();

        /** @var array<string, array{reasons?: list<string>, collected_at?: int}>|mixed $store */
        $store = Cache::pull($key, []);

        if (! is_array($store) || $store === []) {
            $this->setProgress(100);

            return;
        }

        $this->setProgress(40);

        $now = time();
        $documents = [];

        foreach ($store as $documentId => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $collectedAt = (int) ($entry['collected_at'] ?? $now);
            $reasons = $entry['reasons'] ?? [];

            if (! is_array($reasons)) {
                $reasons = [];
            }

            /** @var list<string> $reasonStrings */
            $reasonStrings = array_values(array_map(
                static fn (mixed $reason): string => (string) $reason,
                $reasons,
            ));

            $documents[] = [
                'document_id' => (string) $documentId,
                'reasons' => $reasonStrings,
                'waited_seconds' => max(0, $now - $collectedAt),
            ];
        }

        $this->setProgress(70);

        if ($documents !== []) {
            ReviewNotificationDispatcher::dispatch($documents);
        }

        $this->setProgress(100);
    }

    public function failed(?Throwable $exception = null): void
    {
        Log::error('[EBilling] FlushReviewNotificationBatchJob failed', [
            'batch_key' => ReviewNotificationCache::batchStoreKey(),
            'exception' => $exception,
        ]);
    }
}

