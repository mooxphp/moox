<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Jobs\GenerateArtifactJob;
use Moox\EBilling\Jobs\RecheckMasterDataJob;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Master data synchronised from another system can lag behind the invoices (ADR 0023). After each generation, a
 * document left with a master-data finding is regenerated again after `e-billing.master_data.recheck_after_minutes`,
 * at most `recheck_attempts` times, unless a person has acted on it. Meanwhile it waits in its own tab and reviewers
 * are not notified ({@see EbillingDocument::hasPendingMasterDataRecheck()}).
 */
final class ScheduleMasterDataRecheckAction
{
    /**
     * Reasons master data cannot resolve: the print itself is incomplete or unreadable.
     */
    private const NON_MASTER_DATA_REASONS = ['consignee_address_missing', 'unrecognized', 'printed_differs_from_config'];

    /**
     * Called by {@see GenerateArtifactJob} once field validation is done: schedules the next
     * recheck, or ends a pending one.
     */
    public function execute(EbillingDocument $document): void
    {
        $state = $document->masterDataRecheck();
        $attempt = $state['attempt'] ?? 0;
        $minutes = (int) config('e-billing.master_data.recheck_after_minutes', 0);

        if ($minutes > 0
            && $attempt < (int) config('e-billing.master_data.recheck_attempts', 0)
            && $this->stillApplies($document)) {
            $dueAt = now()->addMinutes($minutes);
            $document->setMasterDataRecheck(['attempt' => $attempt + 1, 'due_at' => $dueAt->toIso8601String()]);
            $document->save();

            RecheckMasterDataJob::dispatch((string) $document->getKey(), $attempt + 1)->delay($dueAt);

            return;
        }

        $this->end($document);
    }

    /**
     * Ends a pending recheck; the attempt count stays, so a later generation does not start over.
     */
    public function end(EbillingDocument $document): void
    {
        $state = $document->masterDataRecheck();
        if ($state === null || ! isset($state['due_at'])) {
            return;
        }

        $document->setMasterDataRecheck(['attempt' => $state['attempt']]);
        $document->save();
    }

    /**
     * Whether a recheck could still change the outcome: the document needs review, master data left a finding,
     * and no person has edited, released, held, approved or rejected the document, nor was it delivered.
     */
    public function stillApplies(EbillingDocument $document): bool
    {
        return $document->needsHumanReview()
            && $this->hasMasterDataFindings($document)
            && ! $this->personActed($document);
    }

    /**
     * A `needs_review` entry on one of `e-billing.master_data.recheck_fields`, on the invoice or on a line, whose
     * reason master data can resolve.
     */
    public function hasMasterDataFindings(EbillingDocument $document): bool
    {
        $fields = config('e-billing.master_data.recheck_fields', []);
        $fields = is_array($fields) ? $fields : [];
        $validations = is_array($document->field_validations) ? $document->field_validations : [];
        $lines = is_array($validations['lines'] ?? null) ? $validations['lines'] : [];

        foreach ([$validations, ...array_filter($lines, is_array(...))] as $entries) {
            foreach ($fields as $field) {
                $entry = $entries[$field] ?? null;
                if (is_array($entry)
                    && ($entry['status'] ?? null) === 'needs_review'
                    && ! in_array($entry['reason'] ?? null, self::NON_MASTER_DATA_REASONS, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function personActed(EbillingDocument $document): bool
    {
        $approval = $document->resolveApprovalStatusEnum();

        return $document->review_changed_at !== null
            || ! empty($document->severity_releases)
            || $document->isHeldForApproval()
            || ($approval !== null && $approval !== DocumentApprovalStatus::Pending)
            || $document->deliveryAttempts()->exists();
    }
}
