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
    public static function hiddenInvoiceFields(): array
    {
        return self::stringList(config('e-billing.invoice_ui.invoice_fields_hidden', []));
    }

    /**
     * @return list<string>
     */
    public static function hiddenLineFields(): array
    {
        return self::stringList(config('e-billing.invoice_ui.invoice_line_fields_hidden', []));
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
    public static function collapsibleState(array $visible, bool $defaultOpen, string $map = 'invoice'): array
    {
        $issueCount = 0;

        foreach ($visible as $field) {
            if (self::priority($field->field, $map) === 'must'
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

    /**
     * @param  'invoice'|'line'  $map
     */
    private static function priority(string $field, string $map): string
    {
        $configKey = $map === 'line'
            ? 'e-billing.field_validation.invoice_line_fields'
            : 'e-billing.field_validation.invoice_fields';

        $fields = config($configKey, []);
        $priority = is_array($fields) ? ($fields[$field] ?? null) : null;

        return is_string($priority) && $priority !== '' ? $priority : 'could';
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $out[] = $item;
            }
        }

        return $out;
    }
}
