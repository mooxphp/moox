<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Moox\EBilling\Enums\ApprovalTransitionKind;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Events\DocumentApprovalTransitioned;
use Moox\EBilling\Models\EbillingDocument;

final class RecordApprovalTransitionAction
{
    public const SYSTEM_ACTOR_ID = 'system';

    /**
     * @param  list<array{field: string, line_id: string|null, reason: string, released_by_id: mixed, released_at: string}>  $forwardedReleaseReasons
     */
    public function execute(
        EbillingDocument $document,
        DocumentApprovalStatus $to,
        ApprovalTransitionKind $kind,
        string $trigger,
        mixed $actorId,
        ?string $actorName,
        ?string $reason = null,
        array $forwardedReleaseReasons = [],
    ): void {
        $from = $document->resolveApprovalStatusEnum();

        if ($from !== null && ! $from->canTransitionTo($to)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot transition approval status from %s to %s.',
                    $from->value,
                    $to->value,
                ),
            );
        }

        if (in_array($kind, [ApprovalTransitionKind::Reject, ApprovalTransitionKind::Restore], true)) {
            $trimmedReason = trim((string) $reason);
            if ($trimmedReason === '') {
                throw new InvalidArgumentException('A reason is required for this approval transition.');
            }
            $reason = $trimmedReason;
        }

        if ($trigger === 'manual') {
            if ($actorId === null || $actorId === '') {
                throw new InvalidArgumentException('An authenticated actor is required for manual approval transitions.');
            }
        } elseif ($trigger === 'auto') {
            $actorId = self::SYSTEM_ACTOR_ID;
            $actorName = $actorName ?? __('e-billing::fields.approval_actor_system');
        }

        $entry = [
            'from' => $from?->value,
            'to' => $to->value,
            'kind' => $kind->value,
            'trigger' => $trigger,
            'at' => Carbon::now()->toIso8601String(),
            'actor_id' => $actorId,
            'actor' => $actorName,
            'reason' => $reason,
            'forwarded_release_reasons' => $forwardedReleaseReasons,
        ];

        if (! EbillingDocument::approvalTransitionEntryIsValid($entry)) {
            throw new InvalidArgumentException('Approval transition entry failed validation.');
        }

        $transitions = is_array($document->approval_transitions) ? $document->approval_transitions : [];
        $transitions[] = $entry;

        $document->approval_status = $to;
        $document->approval_transitions = $transitions;
        $document->save();

        event(new DocumentApprovalTransitioned(
            document: $document,
            from: $from,
            to: $to,
            kind: $kind,
            trigger: $trigger,
        ));
    }
}
