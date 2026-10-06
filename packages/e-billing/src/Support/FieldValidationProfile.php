<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

/**
 * Selects the MoSCoW and ViewInvoice denylist maps by document type (BT-3), ADR 0009.
 *
 * `field_validation.document_type_profiles` maps a type code to a key prefix (e.g. `381` → `credit_note`,
 * `384` → `corrected_invoice`); that type reads the `{prefix}_*` sibling keys and falls back to `invoice_*`
 * when a host omits one. Unmapped types read `invoice_*`.
 */
final class FieldValidationProfile
{
    public const CREDIT_NOTE_DOCUMENT_TYPE = '381';

    private const INVOICE_PREFIX = 'invoice';

    public static function isCreditNote(?string $documentType): bool
    {
        return $documentType !== null && trim($documentType) === self::CREDIT_NOTE_DOCUMENT_TYPE;
    }

    /**
     * @return list<string>
     */
    public static function profiledDocumentTypes(): array
    {
        // Numeric type codes become int array keys; hand them out as the strings BT-3 carries.
        return array_map(strval(...), array_keys(self::profileMap()));
    }

    /**
     * @return array<string, string>
     */
    public static function invoiceFields(?string $documentType): array
    {
        return self::priorityMap(self::resolve('field_validation', 'fields', $documentType));
    }

    /**
     * @return array<string, string>
     */
    public static function lineFields(?string $documentType): array
    {
        return self::priorityMap(self::resolve('field_validation', 'line_fields', $documentType));
    }

    /**
     * Header fields that count as present when every invoice line carries its own value
     * (e.g. one order per line on an invoice that bills several orders).
     *
     * @return list<string>
     */
    public static function invoiceFieldsSatisfiedByLines(?string $documentType): array
    {
        return self::stringList(self::resolve('field_validation', 'fields_satisfied_by_lines', $documentType));
    }

    /**
     * @return list<string>
     */
    public static function contextualShould(?string $documentType, bool $forLines = false): array
    {
        return self::stringList(self::resolve(
            'field_validation',
            $forLines ? 'line_contextual_should' : 'contextual_should',
            $documentType,
        ));
    }

    /**
     * @return list<string>
     */
    public static function hiddenFields(?string $documentType): array
    {
        return self::stringList(self::resolve('invoice_ui', 'fields_hidden', $documentType));
    }

    /**
     * @return list<string>
     */
    public static function hiddenLineFields(?string $documentType): array
    {
        return self::stringList(self::resolve('invoice_ui', 'line_fields_hidden', $documentType));
    }

    public static function priority(string $field, ?string $documentType, bool $isLineField = false): string
    {
        $map = $isLineField ? self::lineFields($documentType) : self::invoiceFields($documentType);

        return $map[$field] ?? 'could';
    }

    /**
     * Mapped document types whose MoSCoW maps differ from the invoice maps; type-blind document
     * queries would judge them by the wrong priorities.
     *
     * @return list<string>
     */
    public static function documentTypesWithOwnPriorities(): array
    {
        return array_values(array_filter(
            self::profiledDocumentTypes(),
            static fn (string $type): bool => self::invoiceFields($type) !== self::invoiceFields(null)
                || self::lineFields($type) !== self::lineFields(null),
        ));
    }

    private static function resolve(string $section, string $suffix, ?string $documentType): mixed
    {
        $prefix = self::profileMap()[trim((string) $documentType)] ?? null;

        if ($prefix !== null) {
            $profile = config("e-billing.{$section}.{$prefix}_{$suffix}");

            if (is_array($profile)) {
                return $profile;
            }
        }

        return config("e-billing.{$section}.".self::INVOICE_PREFIX."_{$suffix}", []);
    }

    /**
     * @return array<string, string> document type code => config key prefix
     */
    private static function profileMap(): array
    {
        $map = config('e-billing.field_validation.document_type_profiles', []);

        if (! is_array($map)) {
            return [];
        }

        $profiles = [];

        foreach ($map as $type => $prefix) {
            if (is_string($prefix) && $prefix !== '' && $prefix !== self::INVOICE_PREFIX) {
                $profiles[(string) $type] = $prefix;
            }
        }

        return $profiles;
    }

    /**
     * @return array<string, string>
     */
    private static function priorityMap(mixed $config): array
    {
        if (! is_array($config)) {
            return [];
        }

        $map = [];

        foreach ($config as $field => $priority) {
            if (is_string($field) && is_string($priority) && $priority !== '') {
                $map[$field] = $priority;
            }
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $item): bool => is_string($item) && $item !== '',
        ));
    }
}
