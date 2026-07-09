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

    /** @var array{exact: array<string, string>, contains: array<string, list<string>>}|null */
    private ?array $lookup = null;

    /** @var array<string, string>|null */
    private ?array $labelsByCode = null;

    public static function resolve(string $documentType): string
    {
        return app(self::class)->resolveLabel($documentType);
    }

    public static function labelForCode(string $code): string
    {
        return app(self::class)->labelFor($code);
    }

    public function resolveLabel(string $documentType): string
    {
        $normalized = mb_strtolower(trim($documentType));

        if ($normalized === '') {
            throw new UnresolvedCodelistLabelException('document_type', $documentType);
        }

        $lookup = $this->lookupMaps();

        if (preg_match('/^\d{2,3}$/', $normalized) === 1 && isset($lookup['labels'][$normalized])) {
            return $normalized;
        }

        if (isset($lookup['exact'][$normalized])) {
            return $lookup['exact'][$normalized];
        }

        $containsMatches = [];

        foreach ($lookup['contains'] as $code => $labels) {
            foreach ($labels as $label) {
                if (str_contains($label, $normalized)) {
                    $containsMatches[$code] = true;

                    break;
                }
            }
        }

        if ($containsMatches !== []) {
            if (isset($containsMatches[self::PRIMARY_CREDIT_NOTE_CODE])) {
                return self::PRIMARY_CREDIT_NOTE_CODE;
            }

            if (isset($containsMatches[self::PRIMARY_INVOICE_CODE])) {
                return self::PRIMARY_INVOICE_CODE;
            }

            return (string) array_key_first($containsMatches);
        }

        Log::warning('Unresolved UNTDID 1001 document type label.', [
            'document_type' => $documentType,
        ]);

        throw new UnresolvedCodelistLabelException('document_type', $documentType);
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
