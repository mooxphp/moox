<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use InvalidArgumentException;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\EBilling\Actions\ClassifyDocumentTypeAction;
use Moox\EBilling\Data\Invoice as InvoiceDto;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\UploadedPdfSource;

/**
 * Document classification (ADR 0011): which document types a reviewer or uploader chooses between,
 * the sign their amounts carry, and how a choice is recorded and applied. Shared by the reviewer switch
 * ({@see ClassifyDocumentTypeAction}), the manual upload and the parsing step.
 */
final class DocumentClassification
{
    public const ACTIVITY_EVENT = 'document_classified';

    /** @var list<string> bill_data amounts negated with the sign of the document type */
    private const DOCUMENT_AMOUNTS = [
        'net_total', 'vat_amount', 'gross_total', 'discount_amount',
        'shipping_cost', 'packaging_cost', 'minimum_quantity_surcharge', 'freight_flat_rate',
        'certificate_cost', 'customs_cost',
    ];

    /** @var list<string> bill_data line amounts negated with the sign of the document type */
    private const LINE_AMOUNTS = ['quantity', 'line_total', 'surcharge_amount', 'material_test_certificate_price'];

    /**
     * Configured classification types with the sign their amounts carry.
     *
     * @return array<string, 'positive'|'negative'>
     */
    public static function signs(): array
    {
        $types = config('e-billing.document_classification.types', []);

        if (! is_array($types)) {
            return [];
        }

        $signs = [];

        foreach ($types as $type => $sign) {
            if ($sign === 'positive' || $sign === 'negative') {
                $signs[(string) $type] = $sign;
            }
        }

        return $signs;
    }

    public static function isClassificationType(string $documentType): bool
    {
        return isset(self::signs()[$documentType]);
    }

    public static function signsDiffer(string $from, string $to): bool
    {
        $signs = self::signs();

        return isset($signs[$from], $signs[$to]) && $signs[$from] !== $signs[$to];
    }

    /**
     * The types a duplicate check compares against: the whole classification family for a classification
     * type (a credit note and a corrected invoice for the same source invoice are one document), else the type itself.
     *
     * @return list<string>
     */
    public static function familyOf(string $documentType): array
    {
        return self::isClassificationType($documentType)
            ? array_map(strval(...), array_keys(self::signs()))
            : [$documentType];
    }

    /**
     * Codes an uploader may declare for a resource's manual upload. Each must be one of the resource's
     * document types, a classification type and an allowed BT-3 code; anything else is a configuration error.
     *
     * @return list<string>
     */
    public static function selectableForUpload(string $resourceKey): array
    {
        $configured = config("e-billing.resources.{$resourceKey}.manual_upload.document_types", []);

        if (! is_array($configured) || $configured === []) {
            return [];
        }

        $resourceTypes = array_map(
            strval(...),
            (array) config("e-billing.resources.{$resourceKey}.document_types", []),
        );
        $allowedCodes = array_map(strval(...), (array) config('e-billing.allowed_document_type_codes', []));
        $selectable = [];

        foreach ($configured as $code) {
            $code = (string) $code;

            if (! in_array($code, $resourceTypes, true)
                || ! self::isClassificationType($code)
                || ! in_array($code, $allowedCodes, true)) {
                throw new InvalidArgumentException(
                    "e-billing.resources.{$resourceKey}.manual_upload.document_types: code {$code} must be one of the "
                    .'resource\'s document_types, a document_classification type and an allowed_document_type_code.',
                );
            }

            $selectable[] = $code;
        }

        return array_values(array_unique($selectable));
    }

    /**
     * The type the uploader declared for the document's source, if any.
     */
    public static function declaredFor(EbillingDocument $document): ?string
    {
        $source = $document->source;
        $declared = $source instanceof UploadedPdfSource ? $source->document_type : null;

        return is_string($declared) && $declared !== '' ? $declared : null;
    }

    /**
     * Applies a declared type to the parsed data. A parsed classification type is replaced (381 and 384
     * cannot be told apart by parsing) and the amounts follow the new sign; any other parsed type is kept
     * and surfaces as a review finding on `document_type`.
     */
    public static function applyDeclaredType(InvoiceDto $parsed, string $declaredType): InvoiceDto
    {
        $parsedType = $parsed->documentTypeCode;

        if ($parsedType === $declaredType || ! self::isClassificationType($parsedType)) {
            return $parsed;
        }

        $data = $parsed->toArray();
        $data['document_type_code'] = $declaredType;
        $data['document_type'] = DocumentClassificationLabels::label($declaredType);

        if (self::signsDiffer($parsedType, $declaredType)) {
            $data = self::negateBillData($data);
        }

        return InvoiceDto::fromArray($data);
    }

    /**
     * Applies the document's declared type to freshly parsed data and logs a resulting reclassification.
     */
    public static function applyDeclaredTypeOnParse(EbillingDocument $document, InvoiceDto $parsed, string $origin): InvoiceDto
    {
        $declaredType = self::declaredFor($document);
        if ($declaredType === null) {
            return $parsed;
        }

        $parsedType = $parsed->documentTypeCode;
        $applied = self::applyDeclaredType($parsed, $declaredType);

        if ($applied->documentTypeCode !== $parsedType) {
            self::recordActivity(
                $document,
                $parsedType,
                $applied->documentTypeCode,
                self::signsDiffer($parsedType, $applied->documentTypeCode),
                $origin,
            );
        }

        return $applied;
    }

    /**
     * Logs the classification as its own activity (never a value correction). Skipped without moox/audit.
     */
    public static function recordActivity(
        EbillingDocument $document,
        ?string $from,
        string $to,
        bool $amountsNegated,
        string $origin,
    ): void {
        if (! class_exists(MooxActivityLogger::class)) {
            return;
        }

        MooxActivityLogger::log('e-billing', self::ACTIVITY_EVENT, [
            'event' => self::ACTIVITY_EVENT,
            'entry_type' => 'log',
            'subject' => $document,
            'properties' => [
                'from' => $from,
                'to' => $to,
                'amounts_negated' => $amountsNegated,
                'origin' => $origin,
            ],
        ]);
    }

    /**
     * Sign flip on parsed data, before persistence. Keep in step with the flip on the persisted invoice,
     * {@see ClassifyDocumentTypeAction::negateAmounts()}: both must cover every amount.
     *
     * @param  array<string, mixed>  $data  {@see InvoiceDto::toArray()}
     * @return array<string, mixed>
     */
    private static function negateBillData(array $data): array
    {
        foreach (self::DOCUMENT_AMOUNTS as $key) {
            if (is_numeric($data[$key] ?? null)) {
                $data[$key] = -(float) $data[$key];
            }
        }

        foreach ($data['lines'] ?? [] as $index => $line) {
            foreach (self::LINE_AMOUNTS as $key) {
                if (is_array($line) && is_numeric($line[$key] ?? null)) {
                    $data['lines'][$index][$key] = -(float) $line[$key];
                }
            }
        }

        return $data;
    }
}
