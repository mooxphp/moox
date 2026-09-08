<?php

declare(strict_types=1);

namespace Moox\EBilling\Approval;

use Illuminate\Support\Facades\Cache;
use Moox\EBilling\Contracts\ReviewNotificationStrategyInterface;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\ReviewNotificationCache;

/**
 * Collects document IDs into a cache store under the configured batch key.
 * Does not dispatch NotifyDocumentsNeedReviewJob; a scheduled flush drains
 * the collection and dispatches one notify job.
 */
final class BatchedReviewNotificationStrategy implements ReviewNotificationStrategyInterface
{
    /**
     * @param  list<string>  $reasons
     */
    public function announce(EbillingDocument $document, array $reasons): void
    {
        $key = ReviewNotificationCache::batchStoreKey();
        $documentId = (string) $document->getKey();

        $raw = Cache::get($key, []);
        /** @var array<string, array{reasons: list<string>, collected_at: int}> $store */
        $store = is_array($raw) ? $raw : [];

        if (! array_key_exists($documentId, $store)) {
            $store[$documentId] = [
                'reasons' => $reasons,
                'collected_at' => time(),
            ];
        }

        Cache::put($key, $store, ReviewNotificationCache::batchTtlSeconds());
    }
}
