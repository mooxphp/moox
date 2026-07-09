<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Facades\Log;
use Moox\Data\Models\StaticUnit;
use Moox\EBilling\Exceptions\CodelistNotImportedException;
use Moox\EBilling\Exceptions\UnresolvedCodelistLabelException;

final class UnitCodeResolver
{
    private const PRIMARY_PIECE_CODE = 'C62';

    /** @var array{exact: array<string, string>, prefix: array<string, string>, labels: array<string, string>}|null */
    private ?array $lookup = null;

    public static function resolve(string $unit): string
    {
        return app(self::class)->resolveLabel($unit);
    }

    public function resolveLabel(string $unit): string
    {
        $normalized = mb_strtolower(trim($unit));

        if ($normalized === '') {
            throw new UnresolvedCodelistLabelException('unit', $unit);
        }

        $lookup = $this->lookupMaps();
        $upper = strtoupper($normalized);

        if (isset($lookup['labels'][$upper])) {
            return $upper;
        }

        if (isset($lookup['exact'][$normalized])) {
            return $lookup['exact'][$normalized];
        }

        foreach ($lookup['prefix'] as $prefix => $code) {
            if ($normalized === $prefix || str_starts_with($normalized, $prefix.' ')) {
                return $code;
            }
        }

        Log::warning('Unresolved Rec 20 unit label.', [
            'unit' => $unit,
        ]);

        throw new UnresolvedCodelistLabelException('unit', $unit);
    }

    /**
     * @return array{exact: array<string, string>, prefix: array<string, string>, labels: array<string, string>}
     */
    private function lookupMaps(): array
    {
        if ($this->lookup !== null) {
            return $this->lookup;
        }

        $records = StaticUnit::query()
            ->orderBy('code')
            ->get(['code', 'common_name', 'symbol']);

        if ($records->isEmpty()) {
            throw new CodelistNotImportedException('static_units');
        }

        $exact = [];
        $prefix = [];
        $labels = [];

        foreach ($records as $record) {
            $code = (string) $record->code;
            $labelCandidates = array_filter([
                mb_strtolower((string) $record->common_name),
                mb_strtolower((string) ($record->symbol ?? '')),
                mb_strtolower(__('data::enums/units.'.$code, [], 'de')),
                mb_strtolower(__('data::enums/units.'.$code, [], 'en')),
            ]);

            $labels[$code] = __('data::enums/units.'.$code, [], 'de');
            if ($labels[$code] === 'data::enums/units.'.$code) {
                $labels[$code] = (string) $record->common_name;
            }

            foreach ($labelCandidates as $candidate) {
                if ($candidate === '') {
                    continue;
                }

                $exact[$candidate] = $code;

                $firstToken = explode(' ', $candidate, 2)[0];
                if ($firstToken !== '') {
                    $prefix[$firstToken] = $code;
                }

                if (str_contains($candidate, ' (')) {
                    $stem = strstr($candidate, ' (', true);
                    if (is_string($stem) && $stem !== '') {
                        $exact[$stem] = $code;
                    }
                }
            }
        }

        if (isset($exact['stück']) || isset($prefix['stück'])) {
            $exact['stück'] = self::PRIMARY_PIECE_CODE;
            $prefix['stück'] = self::PRIMARY_PIECE_CODE;
        }

        return $this->lookup = [
            'exact' => $exact,
            'prefix' => $prefix,
            'labels' => $labels,
        ];
    }
}
