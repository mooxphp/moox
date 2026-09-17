<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Str;
use Moox\Audit\Contracts\ActivityAttributeLabelResolver;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\InvoiceProcessingStatus;

final class EbillingActivityAttributeLabels implements ActivityAttributeLabelResolver
{
    public function resolveFieldLabel(string $field): ?string
    {
        return match ($field) {
            'gateway_status' => __('e-billing::fields.gateway_status'),
            'review_status' => __('e-billing::fields.review_status'),
            'approval_status' => __('e-billing::fields.approval_status'),
            'approval_reason' => __('e-billing::fields.approval_reason'),
            'channel' => __('e-billing::fields.delivery_channel'),
            'recipient' => __('e-billing::fields.delivery_recipient'),
            'success' => __('e-billing::fields.delivery_success'),
            'failure_reason' => __('e-billing::fields.delivery_failure_reason'),
            'correlation_id' => __('e-billing::fields.delivery_correlation_id'),
            'reasons' => __('e-billing::fields.activity_reasons'),
            'waited_seconds' => __('e-billing::fields.activity_waited_seconds'),
            'escalation_level' => __('e-billing::fields.activity_escalation_level'),
            default => $this->invoiceFieldLabelOrNull($field),
        };
    }

    public function resolveValueLabel(string $field, string $value): ?string
    {
        return match ($field) {
            'gateway_status' => EBillingAttachmentProcessingStatus::tryFrom($value)?->label(),
            'review_status' => InvoiceProcessingStatus::tryFrom($value)?->label(),
            'approval_status' => DocumentApprovalStatus::tryFrom($value)?->label(),
            'success' => $this->successValueLabel($value),
            'failure_reason' => DeliveryFailureReasonLabels::label($value),
            default => null,
        };
    }

    private function successValueLabel(string $value): ?string
    {
        return match ($value) {
            'true', '1' => __('e-billing::fields.delivery_outcome_success'),
            'false', '0' => __('e-billing::fields.delivery_outcome_failure'),
            default => null,
        };
    }

    private function invoiceFieldLabelOrNull(string $field): ?string
    {
        $fallback = Str::headline(str_replace('_', ' ', $field));
        $label = InvoiceFieldLabels::get($field);

        return $label !== $fallback ? $label : null;
    }
}
