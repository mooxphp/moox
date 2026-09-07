<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Moox\EBilling\Enums\ApprovalTransitionKind;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Events\DocumentApprovalTransitioned;
use Moox\EBilling\Models\EbillingDocument;

final class RecordApprovalTransitionAction
{
    public const SYSTEM_ACTOR_ID = 'system';

    public function execute(
        EbillingDocument $document,
        DocumentApprovalStatus $to,
        ApprovalTransitionKind $kind,
        string $trigger,
        mixed $actorId,
        ?string $reason = null,
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
        }

        if ($actorId === null || $actorId === '') {
            throw new InvalidArgumentException('An actor is required for this approval transition.');
        }

        $document->approval_status = $to;
        $document->approval_reason = $reason;
        $document->approval_actor_id = (string) $actorId;
        $document->approval_acted_at = Carbon::now();
        $this->saveDocument($document, $trigger);

        event(new DocumentApprovalTransitioned(
            document: $document,
            from: $from,
            to: $to,
            kind: $kind,
            trigger: $trigger,
        ));
    }

    private function saveDocument(EbillingDocument $document, string $trigger): void
    {
        if ($trigger !== 'auto') {
            $document->save();

            return;
        }

        $guard = auth()->guard();
        $previous = $guard->user();

        try {
            $guard->forgetUser();
            $document->save();
        } finally {
            if ($previous instanceof Authenticatable) {
                $guard->setUser($previous);
            }
        }
    }
}
