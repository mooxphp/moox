<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\EBilling\Approval\DocumentEditGuard;
use Moox\EBilling\Enums\ApprovalTransitionKind;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\ForeignDisposition;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\UploadedPdfSource;
use Moox\EBilling\Support\ForeignUploadNotifier;
use RuntimeException;

/**
 * A reviewer decides that a document whose foreign classification was unclear is foreign after all
 * (`e-billing.foreign.review_unclear`). It ends like an automatically ignored foreign document: a mailed one
 * has its source PDF relayed when the disposition is `forward`, an upload's uploader is notified. The generated
 * invoice stays as an unsent draft, the approval is rejected so it is never dispatched, and the decision is
 * final: a relayed PDF cannot be taken back.
 */
final class MarkDocumentForeignAction
{
    public const ACTIVITY_EVENT = 'document_marked_foreign';

    public function __construct(
        private readonly DocumentEditGuard $editGuard,
        private readonly RecordApprovalTransitionAction $recordTransition,
        private readonly RelayForeignSourcePdfAction $relay,
        private readonly ForeignUploadNotifier $uploadNotifier,
    ) {
    }

    /**
     * @return string|null the address the source PDF was relayed to, null when nothing was relayed (an upload, the
     *                     `ignore` disposition, or a terminal relay failure such as a missing recipient)
     */
    public function execute(EbillingDocument $document): ?string
    {
        $user = auth()->user();
        if ($user === null) {
            throw new InvalidArgumentException('An authenticated actor is required to mark a document foreign.');
        }

        // Lock, re-check and relay in one step: two reviewers must not both relay the PDF, which cannot be recalled.
        // A relay that is not accepted throws and rolls the whole decision back.
        $relayedTo = null;
        $marked = DB::transaction(function () use ($document, $user, &$relayedTo): EbillingDocument {
            $locked = $document->newQuery()->lockForUpdate()->findOrFail($document->getKey());
            if (! $locked->awaitsForeignDecision()) {
                throw new InvalidArgumentException("Document #{$document->id} does not await a foreign decision.");
            }

            $this->editGuard->assertPipelineIdle($locked);

            if (! $locked->source instanceof UploadedPdfSource
                && ForeignDisposition::fromConfig() === ForeignDisposition::Forward) {
                $outcome = $this->relay->execute($locked);
                if (! RelayForeignSourcePdfAction::isTerminal($outcome)) {
                    throw new RuntimeException(
                        'The source PDF relay was not accepted: '.($outcome->failureReason ?? 'send_not_accepted'),
                    );
                }

                $relayedTo = $outcome->success ? $outcome->recipient : null;
            }

            $review = $locked->foreignReview() ?? [];
            $locked->gateway_status = EBillingAttachmentProcessingStatus::IgnoredForeign;
            $locked->ignored_reason = [
                'country' => $review['country'] ?? null,
                'matched_rule' => $review['matched_rule'] ?? null,
                'marked_by' => (string) $user->getAuthIdentifier(),
                'classified_at' => now()->utc()->toIso8601String(),
            ];

            $this->recordTransition->executeForAuthenticatedActor(
                document: $locked,
                to: DocumentApprovalStatus::Rejected,
                kind: ApprovalTransitionKind::Reject,
                reason: __('e-billing::fields.approval_reason_marked_foreign'),
            );

            return $locked;
        });

        // The reviewer sees the outcome in the action's own toast; the uploader keeps the bell entry.
        if ($marked->source instanceof UploadedPdfSource) {
            $this->uploadNotifier->notify($marked, toast: false);
        }

        $this->recordActivity($marked, $marked->foreignReview() ?? []);

        return $relayedTo;
    }

    /**
     * @param  array<string, mixed>  $review
     */
    private function recordActivity(EbillingDocument $document, array $review): void
    {
        if (! class_exists(MooxActivityLogger::class)) {
            return;
        }

        MooxActivityLogger::log('e-billing', self::ACTIVITY_EVENT, [
            'event' => self::ACTIVITY_EVENT,
            'entry_type' => 'log',
            'subject' => $document,
            'properties' => array_intersect_key(
                $review,
                array_flip(['matched_rule', 'country', 'vat_amount', 'customer_vat_id']),
            ),
        ]);
    }
}
