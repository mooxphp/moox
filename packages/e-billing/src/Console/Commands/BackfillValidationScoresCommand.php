<?php

declare(strict_types=1);

namespace Moox\EBilling\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Moox\EBilling\Models\EbillingDocument;

class BackfillValidationScoresCommand extends Command
{
    protected $signature = 'ebilling:backfill-scores';

    protected $description = 'Backfill validation_score on ebilling documents that have field_validations but no stored score';

    public function handle(): int
    {
        /** @var Collection<int, EbillingDocument> $documents */
        $documents = EbillingDocument::query()
            ->whereNotNull('field_validations')
            ->whereNull('validation_score')
            ->get();

        $this->info("Backfilling scores for {$documents->count()} documents...");

        $bar = $this->output->createProgressBar($documents->count());

        foreach ($documents as $document) {
            $score = $document->calculateValidationScore();
            if ($score !== null) {
                $document->validation_score = $score;
                $document->saveQuietly();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
