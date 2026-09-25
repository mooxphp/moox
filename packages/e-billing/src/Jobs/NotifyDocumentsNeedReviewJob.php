<?php

declare(strict_types=1);

namespace Moox\EBilling\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Moox\Jobs\Traits\JobProgress;
use Throwable;

/**
 * Host-facing announce that documents need review. Payload has document ids,
 * auto-approve failure reasons, and wait time — no recipients and no wording.
 * The package does not send mail.
 */
final class NotifyDocumentsNeedReviewJob implements ShouldQueue
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

    /**
     * @param  list<array{document_id: string, reasons: list<string>, waited_seconds: int, escalation_level?: string}>  $documents
     */
    public function __construct(
        public array $documents,
    ) {
    }

    public function handle(): void
    {
        $this->setProgress(0);

        Log::info('[EBilling] NotifyDocumentsNeedReviewJob: documents need review', [
            'document_count' => count($this->documents),
            'document_ids' => $this->documentIds(),
        ]);

        $this->setProgress(100);
    }

    public function failed(?Throwable $exception = null): void
    {
        Log::error('[EBilling] NotifyDocumentsNeedReviewJob failed', [
            'document_count' => count($this->documents),
            'document_ids' => $this->documentIds(),
            'exception' => $exception,
        ]);
    }

    /**
     * @return list<string>
     */
    private function documentIds(): array
    {
        return array_map(
            static fn (array $item): string => $item['document_id'],
            $this->documents,
        );
    }
}
