<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Moox\EBilling\Approval\DocumentEditGuard;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Jobs\GenerateArtifactJob;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\InvoiceFieldValidator;
use Throwable;

/**
 * Confirmed leave from the review workspace, the single reviewer trigger for rematch, regeneration and
 * re-validation (ADR 0004, mooxphp/e-billing#48). It always re-runs matching and the field checks now, so
 * fixed master data is picked up (manual attribution is left untouched by {@see InvoiceFieldValidator}).
 * Only a review change newer than the artifact queues the existing generate-then-validate path.
 */
final class LeaveEditAction
{
    public function __construct(
        private readonly InvoiceFieldValidator $validator,
        private readonly DocumentEditGuard $editGuard,
    ) {
    }

    /**
     * @return bool true when regeneration and re-validation were queued
     */
    public function execute(EbillingDocument $document): bool
    {
        $started = DB::transaction(function () use ($document): bool {
            $locked = $document->newQuery()->lockForUpdate()->findOrFail($document->getKey());

            if ($locked->resolveApprovalStatusEnum() !== DocumentApprovalStatus::Pending) {
                throw new InvalidArgumentException('Only a pending document can leave the review workspace.');
            }

            $this->editGuard->assertPipelineIdle($locked);

            // Sanctioned backward move: the automatic path must not re-enter confirmed / validated.
            $locked->review_status = InvoiceProcessingStatus::ParserCreated;
            $locked->save();

            $this->validator->validate($locked);

            $locked->refresh();
            if (! $locked->hasReviewChangesSinceArtifact()) {
                return false;
            }

            // GenerateArtifactJob refills field validations; that guard refuses validated / human_confirmed.
            // Leave-edit may have just transitioned to validated when the last must-finding was cleared.
            $locked->review_status = InvoiceProcessingStatus::ParserCreated;
            $locked->gateway_status = EBillingAttachmentProcessingStatus::Generating;
            $locked->save();

            return true;
        });

        if (! $started) {
            return false;
        }

        try {
            GenerateArtifactJob::dispatch((string) $document->getKey());
        } catch (Throwable $exception) {
            // Never leave the document locked in `generating` without a job that would end it. Saved through
            // the model so the audited `generation_failed` status is recorded like any other gateway outcome.
            $failed = $document->newQuery()->findOrFail($document->getKey());
            $failed->gateway_status = EBillingAttachmentProcessingStatus::GenerationFailed;
            $failed->save();

            throw $exception;
        }

        return true;
    }
}
