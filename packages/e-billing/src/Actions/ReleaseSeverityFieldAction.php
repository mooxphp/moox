<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\InvoiceFieldValidator;

final class ReleaseSeverityFieldAction
{
    public function __construct(
        private readonly InvoiceFieldValidator $validator,
    ) {
    }

    /**
     * Records a severity release for an absent recommended (should) field.
     * Applies to missing fields only; wrong content cannot be released.
     */
    public function execute(EbillingDocument $document, string $field, string $reason, ?string $lineId = null): void
    {
        $trimmedReason = trim($reason);

        if ($trimmedReason === '') {
            throw new InvalidArgumentException('A reason is required to release a missing recommended field.');
        }

        $priority = $document->resolveConfiguredFieldPriority($field, $lineId !== null);

        if ($priority !== 'should') {
            throw new InvalidArgumentException(
                "Only missing recommended (should) fields can be severity-released; '{$field}' is '{$priority}'."
            );
        }

        $status = $document->resolveFieldValidationStatus($field, $lineId);

        if ($status !== 'missing') {
            throw new InvalidArgumentException(
                'Severity release applies only to absent fields with status missing.'
            );
        }

        $user = auth()->user();

        if ($user === null) {
            throw new InvalidArgumentException('An authenticated actor is required to release a missing recommended field.');
        }

        $releasedAt = Carbon::now()->toIso8601String();

        $releases = is_array($document->severity_releases) ? $document->severity_releases : [];

        $entry = [
            'released_at' => $releasedAt,
            'released_by_id' => $user->getAuthIdentifier(),
            'released_by' => $user->name,
            'reason' => $trimmedReason,
        ];

        if ($lineId !== null) {
            $lines = is_array($releases['lines'] ?? null) ? $releases['lines'] : [];
            $lineReleases = is_array($lines[$lineId] ?? null) ? $lines[$lineId] : [];
            $lineReleases[$field] = $entry;
            $lines[$lineId] = $lineReleases;
            $releases['lines'] = $lines;
        } else {
            $releases[$field] = $entry;
        }

        $document->severity_releases = $releases;
        $document->save();

        $this->validator->refreshReviewOutcome($document->fresh());

        $fresh = $document->fresh();
        if ($fresh instanceof EbillingDocument) {
            app(TryAutoApproveDocumentAction::class)->execute($fresh);
        }
    }
}
