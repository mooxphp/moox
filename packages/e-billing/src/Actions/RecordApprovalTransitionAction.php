<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Moox\EBilling\Enums\ApprovalTransitionKind;
use Moox\EBilling\Enums\ApprovalTrigger;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Events\DocumentApprovalTransitioned;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\ForwardedSeverityRelease;
use Moox\EBilling\Support\SeverityReleaseSnapshotCollector;

final class RecordApprovalTransitionAction
{
    public const SYSTEM_ACTOR_ID = 'system';

    /**
     * @param  list<ForwardedSeverityRelease>  $forwardedReleaseReasons
     */
    public function execute(
        EbillingDocument $document,
        DocumentApprovalStatus $to,
        ApprovalTransitionKind $kind,
        ApprovalTrigger $trigger,
        mixed $actorId,
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

        if ($kind === ApprovalTransitionKind::Approve) {
            $reason = $this->resolveApproveReason($reason, $forwardedReleaseReasons);
        }

        if ($trigger === ApprovalTrigger::Manual) {
            if ($actorId === null || $actorId === '') {
                throw new InvalidArgumentException('An authenticated actor is required for manual approval transitions.');
            }
        } else {
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

    /**
     * @param  list<ForwardedSeverityRelease>  $forwardedReleaseReasons
     */
    public function executeForAuthenticatedActor(
        EbillingDocument $document,
        DocumentApprovalStatus $to,
        ApprovalTransitionKind $kind,
        ?string $reason = null,
        array $forwardedReleaseReasons = [],
    ): void {
        $user = auth()->user();

        if ($user === null) {
            throw new InvalidArgumentException('An authenticated actor is required for this approval transition.');
        }

        $this->execute(
            document: $document,
            to: $to,
            kind: $kind,
            trigger: ApprovalTrigger::Manual,
            actorId: $user->getAuthIdentifier(),
            reason: $reason,
            forwardedReleaseReasons: $forwardedReleaseReasons,
        );
    }

    /**
     * @param  list<ForwardedSeverityRelease>  $forwardedReleaseReasons
     */
    private function resolveApproveReason(?string $reason, array $forwardedReleaseReasons): ?string
    {
        $trimmed = trim((string) $reason);

        if ($trimmed !== '') {
            return $trimmed;
        }

        return SeverityReleaseSnapshotCollector::formatAsApprovalReason($forwardedReleaseReasons);
    }

    private function saveDocument(EbillingDocument $document, ApprovalTrigger $trigger): void
    {
        if ($trigger !== ApprovalTrigger::Auto) {
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
