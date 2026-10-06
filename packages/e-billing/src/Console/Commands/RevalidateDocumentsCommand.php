<?php

declare(strict_types=1);

namespace Moox\EBilling\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Moox\EBilling\Actions\RevalidateDocumentAction;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Re-validates documents and regenerates their artifact from the stored Invoice after a validation or
 * emission change; see {@see RevalidateDocumentAction} for which documents qualify.
 */
class RevalidateDocumentsCommand extends Command
{
    protected $signature = 'e-billing:revalidate
        {ids?* : E-billing document ids}
        {--in-review : Select documents that need human review}
        {--hold : Keep the selected documents from auto-approval until a person approves them}
        {--dry-run : List the selection without changing anything}';

    protected $description = 'Re-validate e-billing documents and regenerate the artifact from the stored invoice, keeping review corrections';

    public function handle(RevalidateDocumentAction $action): int
    {
        /** @var list<string> $ids */
        $ids = $this->argument('ids');
        $inReview = (bool) $this->option('in-review');

        if ($ids === [] && ! $inReview) {
            $this->warn('Nothing selected. Pass document ids or --in-review.');

            return self::SUCCESS;
        }

        /** @var Collection<int, EbillingDocument> $documents */
        $documents = EbillingDocument::query()
            ->when($ids !== [], fn ($query) => $query->whereKey($ids))
            ->when($inReview, fn ($query) => $query->needsHumanReview())
            ->get();

        $this->info("Selected {$documents->count()} document(s).");

        if ($this->option('dry-run')) {
            $this->table(['id', 'review status', 'gateway status', 'outcome'], $documents->map(
                function (EbillingDocument $document) use ($action): array {
                    $refusal = $action->refusal($document);

                    return [
                        $document->getKey(),
                        $document->review_status?->value,
                        $document->gateway_status?->value,
                        $refusal === null ? 'revalidate' : "refuse: {$refusal}",
                    ];
                },
            )->all());

            return self::SUCCESS;
        }

        $refused = 0;
        foreach ($documents as $document) {
            $refusal = $action->execute($document, (bool) $this->option('hold'));

            if ($refusal === null) {
                $this->line("Revalidating {$document->getKey()}");

                continue;
            }

            $refused++;
            $this->warn("Refused {$document->getKey()}: {$refusal}");
        }

        $this->info(($documents->count() - $refused)." queued, {$refused} refused. Generation and validation run on the queue.");

        return self::SUCCESS;
    }
}
