<?php

declare(strict_types=1);

namespace Moox\EBilling\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Moox\EBilling\Actions\ReparseDocumentAction;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Re-parses documents from their source PDF after a parser fix and queues regeneration plus KoSIT
 * validation; see {@see ReparseDocumentAction} for which documents qualify.
 */
class ReparseDocumentsCommand extends Command
{
    protected $signature = 'e-billing:reparse
        {ids?* : E-billing document ids}
        {--kosit-failed : Select documents whose latest KoSIT validation failed}
        {--dry-run : List the selection without changing anything}';

    protected $description = 'Re-parse untouched e-billing documents from their source PDF and regenerate the artifact';

    public function handle(ReparseDocumentAction $action): int
    {
        /** @var list<string> $ids */
        $ids = $this->argument('ids');
        $kositFailed = (bool) $this->option('kosit-failed');

        if ($ids === [] && ! $kositFailed) {
            $this->warn('Nothing selected. Pass document ids or --kosit-failed.');

            return self::SUCCESS;
        }

        /** @var Collection<int, EbillingDocument> $documents */
        $documents = EbillingDocument::query()
            ->when($ids !== [], fn ($query) => $query->whereKey($ids))
            ->when($kositFailed, fn ($query) => $query->whereLatestKositValidationPassed(false))
            ->get();

        $this->info("Selected {$documents->count()} document(s).");

        if ($this->option('dry-run')) {
            $rows = $documents->map(function (EbillingDocument $document) use ($action): array {
                $refusal = $action->refusal($document);

                return [
                    $document->getKey(),
                    $document->review_status?->value,
                    $document->gateway_status?->value,
                    $refusal === null ? 'reparse' : "refuse: {$refusal}",
                ];
            });
            $this->table(['id', 'review status', 'gateway status', 'outcome'], $rows->all());

            return self::SUCCESS;
        }

        $refused = 0;
        foreach ($documents as $document) {
            $outcome = $action->execute($document);

            if ($outcome->reparsed) {
                $this->line("Reparsed {$document->getKey()}: net {$outcome->oldNetTotal} → {$outcome->newNetTotal}, "
                    ."lines {$outcome->oldLineTotal} → {$outcome->newLineTotal}");

                continue;
            }

            $refused++;
            $this->warn("Refused {$document->getKey()}: {$outcome->refusal}");
        }

        $this->info(($documents->count() - $refused)." reparsed, {$refused} refused. Generation and KoSIT validation run on the queue.");

        return self::SUCCESS;
    }
}
