<?php

declare(strict_types=1);

namespace Moox\EBilling\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Moox\EBilling\Support\DeliveryFailureReasonLabels;

/**
 * One delivery attempt for a document on one channel to one recipient.
 *
 * @property string $ebilling_document_id
 * @property string $channel
 * @property string $recipient
 * @property bool $success
 * @property string|null $failure_reason
 * @property string|null $correlation_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class EbillingDeliveryAttempt extends Model
{
    use HasUuids;

    protected $table = 'ebilling_delivery_attempts';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ebilling_document_id',
        'channel',
        'recipient',
        'success',
        'failure_reason',
        'correlation_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<EbillingDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(EbillingDocument::class, 'ebilling_document_id');
    }

    protected function failureReasonLabel(): Attribute
    {
        return Attribute::get(
            fn (): ?string => DeliveryFailureReasonLabels::label($this->failure_reason),
        );
    }

    protected function successLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->success
                ? __('e-billing::fields.delivery_outcome_success')
                : __('e-billing::fields.delivery_outcome_failure'),
        );
    }
}
