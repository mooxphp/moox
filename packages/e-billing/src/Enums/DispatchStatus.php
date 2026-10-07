<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

use Illuminate\Database\Eloquent\Builder;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Whether a document went out to its recipient, and if not, what it waits for. Derived from the
 * approval state, the hold and the delivery attempts; never stored.
 */
enum DispatchStatus: string
{
    case Sent = 'sent';
    case DeliveryFailed = 'delivery_failed';
    case Held = 'held';
    case AwaitingApproval = 'awaiting_approval';
    case Rejected = 'rejected';

    /**
     * Null while the document is not ready to be sent (no approval state yet).
     */
    public static function forDocument(?EbillingDocument $document): ?self
    {
        if ($document === null) {
            return null;
        }

        $attempts = $document->deliveryAttempts;
        if ($attempts->isNotEmpty()) {
            return $attempts->contains('success', true) ? self::Sent : self::DeliveryFailed;
        }

        return match ($document->resolveApprovalStatusEnum()) {
            DocumentApprovalStatus::Pending => $document->isHeldForApproval() ? self::Held : self::AwaitingApproval,
            DocumentApprovalStatus::Rejected => self::Rejected,
            default => null,
        };
    }

    /**
     * Narrows an e-billing document query to this status, matching {@see forDocument()}.
     *
     * @template TDocument of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TDocument>  $documentQuery  a query on the e-billing document (e.g. inside whereHas)
     * @return Builder<TDocument>
     */
    public function constrain(Builder $documentQuery): Builder
    {
        return match ($this) {
            self::Sent => $documentQuery->whereHas('deliveryAttempts', fn (Builder $attempts) => $attempts->where('success', true)),
            self::DeliveryFailed => $documentQuery
                ->whereHas('deliveryAttempts')
                ->whereDoesntHave('deliveryAttempts', fn (Builder $attempts) => $attempts->where('success', true)),
            self::Held => $documentQuery->doesntHave('deliveryAttempts')
                ->where('approval_status', DocumentApprovalStatus::Pending->value)
                ->whereNotNull('approval_flags->held'),
            self::AwaitingApproval => $documentQuery->doesntHave('deliveryAttempts')
                ->where('approval_status', DocumentApprovalStatus::Pending->value)
                ->whereNull('approval_flags->held'),
            self::Rejected => $documentQuery->doesntHave('deliveryAttempts')
                ->where('approval_status', DocumentApprovalStatus::Rejected->value),
        };
    }

    public function label(): string
    {
        return __('e-billing::fields.dispatch_status_'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Sent => 'success',
            self::DeliveryFailed => 'danger',
            self::Held, self::AwaitingApproval => 'warning',
            self::Rejected => 'gray',
        };
    }
}
