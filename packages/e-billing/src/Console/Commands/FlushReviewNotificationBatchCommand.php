<?php

declare(strict_types=1);

namespace Moox\EBilling\Console\Commands;

use Illuminate\Console\Command;
use Moox\EBilling\Jobs\FlushReviewNotificationBatchJob;

final class FlushReviewNotificationBatchCommand extends Command
{
    protected $signature = 'e-billing:flush-review-notification-batch';

    protected $description = 'Dispatch FlushReviewNotificationBatchJob to drain the batch store into one NotifyDocumentsNeedReviewJob';

    public function handle(): int
    {
        FlushReviewNotificationBatchJob::dispatch();

        $this->info('Dispatched FlushReviewNotificationBatchJob (drains batch → NotifyDocumentsNeedReviewJob).');

        return self::SUCCESS;
    }
}
