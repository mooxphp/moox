<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\ViewModels\FieldViewData;

/**
 * ViewInvoice denylist + collapsible state (ADR 0007). Does not change MoSCoW validation.
 */
final class InvoiceUiPresentation
{
    /** @var list<string> */
    private const BLOCKING_STATUSES = ['missing', 'invalid', 'unmatched', 'needs_review'];

    /**
     * @param  list<FieldViewData>  $fields
     * @param  list<string>  $hidden
     * @return list<FieldViewData>
     */
    public static function withoutHidden(array $fields, array $hidden): array
    {
        if ($hidden === []) {
            return $fields;
        }

        $hiddenLookup = array_fill_keys($hidden, true);

        return array_values(array_filter(
            $fields,
            static fn (FieldViewData $field): bool => ! isset($hiddenLookup[$field->field]),
        ));
    }

    /**
     * @return list<string>
     */
    public static function hiddenInvoiceFields(?string $documentType = null): array
    {
        return FieldValidationProfile::hiddenFields($documentType);
    }

    /**
     * @return list<string>
     */
    public static function hiddenLineFields(?string $documentType = null): array
    {
        return FieldValidationProfile::hiddenLineFields($documentType);
    }

    public static function groupDefaultOpen(string $group): bool
    {
        return (bool) config("e-billing.invoice_ui.field_groups.{$group}.default_open", true);
    }

    /**
     * @param  list<FieldViewData>  $visible  Already denylist-filtered
     * @param  'invoice'|'line'  $map
     * @return array{open: bool, issue_count: int, issue_label: ?string}
     */
    public static function collapsibleState(
        array $visible,
        bool $defaultOpen,
        string $map = 'invoice',
        ?string $documentType = null,
    ): array {
        $issueCount = 0;

        foreach ($visible as $field) {
            if (FieldValidationProfile::priority($field->field, $documentType, $map === 'line') === 'must'
                && in_array($field->status(), self::BLOCKING_STATUSES, true)) {
                $issueCount++;
            }
        }

        return [
            'open' => $defaultOpen || $issueCount > 0,
            'issue_count' => $issueCount,
            'issue_label' => $issueCount > 0
                ? trans_choice('e-billing::fields.group_must_issues', $issueCount, ['count' => $issueCount])
                : null,
        ];
    }
}
