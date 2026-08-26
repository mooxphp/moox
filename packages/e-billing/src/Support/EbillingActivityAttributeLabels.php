<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Str;
use Moox\Audit\Contracts\ActivityAttributeLabelResolver;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\InvoiceProcessingStatus;

final class EbillingActivityAttributeLabels implements ActivityAttributeLabelResolver
{
    public function resolveFieldLabel(string $field): ?string
    {
        return match ($field) {
            'gateway_status' => __('e-billing::fields.gateway_status'),
            'review_status' => __('e-billing::fields.review_status'),
            default => $this->invoiceFieldLabelOrNull($field),
        };
    }

    public function resolveValueLabel(string $field, string $value): ?string
    {
        return match ($field) {
            'gateway_status' => EBillingAttachmentProcessingStatus::tryFrom($value)?->label(),
            'review_status' => InvoiceProcessingStatus::tryFrom($value)?->label(),
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
