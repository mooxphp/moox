<?php

declare(strict_types=1);

namespace Moox\Data\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Moox\Core\Entities\Items\Static\BaseStaticModel;
use Moox\Data\Support\CodelistEnumTranslations;
use Moox\Data\Support\CodelistRegistry;
use RuntimeException;

class ImportCodelistsService
{
    /** @var list<string> */
    private const STRUCTURAL_ROW_ATTRIBUTES = [
        'en16931_interpretation',
        'symbol',
        'version',
        'vat_category_code',
    ];

    public function import(?string $scheme = null): int
    {
        $entries = $scheme !== null
            ? array_filter([$scheme => CodelistRegistry::get($scheme)], fn ($entry) => $entry !== null)
            : CodelistRegistry::all();

        if ($scheme !== null && $entries === []) {
            throw new RuntimeException("Unknown codelist scheme [{$scheme}].");
        }

        $imported = 0;

        foreach ($entries as $schemeKey => $entry) {
            $imported += $this->importScheme((string) $schemeKey, $entry);
        }

        return $imported;
    }

    /**
     * @param  array{file: string, model: class-string, upsert_keys?: list<string>}  $entry
     */
    protected function importScheme(string $scheme, array $entry): int
    {
        $path = $this->resolveJsonPath($entry['file']);

        if (! File::isFile($path)) {
            throw new RuntimeException("Codelist JSON not found for [{$scheme}]: {$path}");
        }

        $payload = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($payload) || ! isset($payload['codes']) || ! is_array($payload['codes'])) {
            throw new RuntimeException("Invalid codelist JSON shape for [{$scheme}]: missing codes array.");
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $entry['model'];
        $upsertKeys = $entry['upsert_keys'] ?? ['code'];
        $count = 0;

        foreach ($payload['codes'] as $row) {
            if (! is_array($row) || ! isset($row['common_name'])) {
                Log::channel('daily')->warning("Skipping invalid codelist row in [{$scheme}].", ['row' => $row]);

                continue;
            }

            foreach ($upsertKeys as $key) {
                if (! isset($row[$key])) {
                    Log::channel('daily')->warning("Skipping codelist row missing upsert key [{$key}] in [{$scheme}].", ['row' => $row]);

                    continue 2;
                }
            }

            $criteria = [];
            foreach ($upsertKeys as $key) {
                $criteria[$key] = (string) $row[$key];
            }

            $attributes = [];

            foreach (self::STRUCTURAL_ROW_ATTRIBUTES as $attribute) {
                if (array_key_exists($attribute, $row)) {
                    $attributes[$attribute] = $row[$attribute] !== null
                        ? (string) $row[$attribute]
                        : null;
                }
            }

            /** @var BaseStaticModel $record */
            $record = $modelClass::updateOrCreate($criteria, $attributes);
            $this->syncTranslations($record, $scheme, $row);

            $count++;
        }

        Log::channel('daily')->info("Imported {$count} rows for codelist [{$scheme}] from {$entry['file']}.");

        return $count;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function syncTranslations(BaseStaticModel $record, string $scheme, array $row): void
    {
        $code = (string) $row['code'];
        $description = array_key_exists('description', $row) && $row['description'] !== null
            ? (string) $row['description']
            : null;

        $english = $record->translateOrNew('en');
        $english->common_name = (string) $row['common_name'];
        $english->description = $description;
        $english->save();

        foreach (CodelistEnumTranslations::SHIPPED_LOCALES as $locale) {
            if ($locale === 'en') {
                continue;
            }

            $label = CodelistEnumTranslations::labelFor($scheme, $code, $locale);

            if ($label === null) {
                continue;
            }

            $translation = $record->translateOrNew($locale);
            $translation->common_name = $label;
            $translation->save();
        }
    }

    protected function resolveJsonPath(string $filename): string
    {
        return dirname(__DIR__, 2).'/database/data/codelists/'.$filename;
    }
}
