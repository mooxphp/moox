<?php

declare(strict_types=1);

namespace Moox\EBilling\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Moox\EBilling\Actions\DispatchDocumentAction;
use Moox\EBilling\Models\EbillingDocument;
use Moox\Jobs\Traits\JobProgress;
use Throwable;

final class DispatchDocumentJob implements ShouldQueue
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

    public function __construct(
        public string $documentId,
    ) {
    }

    public function handle(DispatchDocumentAction $action): void
    {
        $this->setProgress(0);

        $document = EbillingDocument::query()->find($this->documentId);

        if ($document === null) {
            Log::warning('[EBilling] DispatchDocumentJob: document missing', [
                'document_id' => $this->documentId,
            ]);
            $this->setProgress(100);

            return;
        }

        $this->setProgress(20);
        $action->execute($document);
        $this->setProgress(100);
    }

    public function failed(?Throwable $exception = null): void
    {
        Log::error('[EBilling] DispatchDocumentJob failed', [
            'document_id' => $this->documentId,
            'exception' => $exception,
        ]);
    }
}
