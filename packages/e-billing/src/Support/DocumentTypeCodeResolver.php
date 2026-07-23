<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Facades\Log;
use Moox\Data\Models\StaticDocumentType;
use Moox\EBilling\Exceptions\CodelistNotImportedException;
use Moox\EBilling\Exceptions\UnresolvedCodelistLabelException;

final class DocumentTypeCodeResolver
{
    private const PRIMARY_INVOICE_CODE = '380';

    private const PRIMARY_CREDIT_NOTE_CODE = '381';

    /**
     * @var array{
     *     exact: array<string, string>,
     *     contains: array<string, list<string>>,
     *     labels: array<string, string>,
     * }|null
     */
    private ?array $lookup = null;

    public static function resolve(string $documentType): string
    {
        return app(self::class)->resolveLabel($documentType);
    }

    public static function labelForCode(string $code): string
    {
        return app(self::class)->labelFor($code);
    }

    public function resolveFromCodeOrLabel(string $documentTypeCode, string $documentType): string
    {
        if ($documentTypeCode !== '') {
            return $this->assertAllowed($documentTypeCode, 'document_type_code', $documentTypeCode);
        }

        return $this->resolveLabel($documentType);
    }

    public function resolveLabel(string $documentType): string
    {
        $normalized = mb_strtolower(trim($documentType));

        if ($normalized === '') {
            throw new UnresolvedCodelistLabelException('document_type', $documentType);
        }

        $lookup = $this->lookupMaps();

        if (preg_match('/^\d{2,3}$/', $normalized) === 1 && isset($lookup['labels'][$normalized])) {
            return $this->assertAllowed($normalized, 'document_type', $documentType);
        }

        if (isset($lookup['exact'][$normalized])) {
            return $this->assertAllowed($lookup['exact'][$normalized], 'document_type', $documentType);
        }

        $byContains = $this->resolveByContains($lookup, $normalized, $documentType);
        if ($byContains !== null) {
            return $byContains;
        }

        Log::warning('Unresolved UNTDID 1001 document type label.', [
            'document_type' => $documentType,
        ]);

        throw new UnresolvedCodelistLabelException('document_type', $documentType);
    }

    /**
     * Contains-match with priority ladder (credit note 381 > invoice 380 > first match).
     *
     * @param  array{
     *     exact: array<string, string>,
     *     contains: array<string, list<string>>,
     *     labels: array<string, string>,
     * }  $lookup
     */
    private function resolveByContains(array $lookup, string $normalized, string $documentType): ?string
    {
        $allowed = array_flip($this->allowedCodes());
        $containsMatches = [];

        foreach ($lookup['contains'] as $code => $labels) {
            if (! isset($allowed[$code])) {
                continue;
            }

            foreach ($labels as $label) {
                if (str_contains($label, $normalized)) {
                    $containsMatches[$code] = true;

                    break;
                }
            }
        }

        if ($containsMatches === []) {
            return null;
        }

        if (isset($containsMatches[self::PRIMARY_CREDIT_NOTE_CODE])) {
            return self::PRIMARY_CREDIT_NOTE_CODE;
        }

        if (isset($containsMatches[self::PRIMARY_INVOICE_CODE])) {
            return self::PRIMARY_INVOICE_CODE;
        }

        return $this->assertAllowed((string) array_key_first($containsMatches), 'document_type', $documentType);
    }

    public function labelFor(string $code): string
    {
        $code = trim($code);

        if ($code === '') {
            throw new UnresolvedCodelistLabelException('document_type_code', $code);
        }

        $labels = $this->lookupMaps()['labels'];

        if (! isset($labels[$code])) {
            throw new UnresolvedCodelistLabelException('document_type_code', $code);
        }

        return $labels[$code];
    }

    /**
     * @return list<string>
     */
    private function allowedCodes(): array
    {
        /** @var list<string|int>|array<int, string|int> $codes */
        $codes = config('e-billing.allowed_document_type_codes', ['380', '381']);

        return array_values(array_map(
            static fn (string|int $code): string => (string) $code,
            $codes,
        ));
    }

    private function assertAllowed(string $code, string $codelist, string $input): string
    {
        if (! in_array($code, $this->allowedCodes(), true)) {
            throw new UnresolvedCodelistLabelException($codelist, $input);
        }

        return $code;
    }

    /**
     * @return array{exact: array<string, string>, contains: array<string, list<string>>, labels: array<string, string>}
     */
    private function lookupMaps(): array
    {
        if ($this->lookup !== null) {
            return $this->lookup;
        }

        $records = StaticDocumentType::query()
            ->orderBy('code')
            ->get(['code', 'common_name']);

        if ($records->isEmpty()) {
            throw new CodelistNotImportedException('static_document_types');
        }

        $exact = [];
        $contains = [];
        $labels = [];

        foreach ($records as $record) {
            $code = (string) $record->code;
            $labelCandidates = array_filter([
                mb_strtolower((string) $record->common_name),
                mb_strtolower(__('data::enums/document-types.'.$code, [], 'de')),
                mb_strtolower(__('data::enums/document-types.'.$code, [], 'en')),
            ]);

            $labels[$code] = __('data::enums/document-types.'.$code, [], 'de');
            if ($labels[$code] === 'data::enums/document-types.'.$code) {
                $labels[$code] = (string) $record->common_name;
            }

            $contains[$code] = array_values(array_unique($labelCandidates));

            foreach ($labelCandidates as $candidate) {
                if ($candidate === '') {
                    continue;
                }

                $exact[$candidate] = $code;
            }
        }

        return $this->lookup = [
            'exact' => $exact,
            'contains' => $contains,
            'labels' => $labels,
        ];
    }
}
