<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Execution;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moox\Transform\Contracts\BatchDestinationWriter;
use Moox\Transform\Models\TransformDefinition;

final class TranslatableBatchDestinationWriter implements BatchDestinationWriter
{
    public function supports(string $destinationClass, TransformDefinition $definition): bool
    {
        /** @var Model $prototype */
        $prototype = new $destinationClass;

        return $this->resolveTranslatedAttributes($prototype) !== []
            && property_exists($prototype, 'translationModel')
            && is_string($prototype->translationModel)
            && $prototype->translationModel !== '';
    }

    public function write(string $destinationClass, TransformDefinition $definition, array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        /** @var Model $prototype */
        $prototype = new $destinationClass;
        $translatedAttributes = $this->resolveTranslatedAttributes($prototype);
        $mainRecordsByKey = [];
        $uniqueBy = array_keys($rows[0]->destinationMatch);

        foreach ($rows as $row) {
            $mainRecord = [];
            foreach ($row->resolvedData as $field => $value) {
                if (in_array($field, $translatedAttributes, true) || $field === 'locale') {
                    continue;
                }

                $mainRecord[$field] = $value;
            }

            if ($mainRecord === []) {
                continue;
            }

            $mainRecordsByKey[$this->recordKey($row->destinationMatch, $uniqueBy)] = $this->withTimestamps($prototype, $mainRecord);
        }

        $mainRecords = array_values($mainRecordsByKey);

        $updateColumns = $mainRecords === []
            ? []
            : array_values(array_diff(array_keys($mainRecords[0]), $uniqueBy, ['created_at']));

        if ($mainRecords !== []) {
            DB::table($prototype->getTable())->upsert($mainRecords, $uniqueBy, $updateColumns);
        }

        /** @var class-string<Model> $translationModelClass */
        $translationModelClass = $prototype->translationModel;
        $translationPrototype = new $translationModelClass;
        $translationForeignKey = (string) $prototype->translationForeignKey;
        $localeKey = is_string($prototype->localeKey ?? null) && $prototype->localeKey !== ''
            ? $prototype->localeKey
            : 'locale';

        $translationRecords = [];
        $destinationKeys = [];

        foreach ($rows as $row) {
            $destinationKey = $this->resolveDestinationKey($destinationClass, $row->destinationMatch);
            $destinationKeys[] = $destinationKey;

            if ($destinationKey === '') {
                continue;
            }

            $translationRecord = [
                $translationForeignKey => $destinationKey,
                $localeKey => is_string($row->resolvedData['locale'] ?? null) && $row->resolvedData['locale'] !== ''
                    ? $row->resolvedData['locale']
                    : (string) config('transform.default_locale', app()->getLocale()),
            ];

            foreach ($translatedAttributes as $attribute) {
                if (array_key_exists($attribute, $row->resolvedData)) {
                    $translationRecord[$attribute] = $row->resolvedData[$attribute];
                }
            }

            $translationRecords[] = $this->withTimestamps($translationPrototype, $translationRecord);
        }

        if ($translationRecords !== []) {
            $translationUpdateColumns = array_values(array_diff(
                array_keys($translationRecords[0]),
                [$translationForeignKey, $localeKey, 'created_at']
            ));

            DB::table($translationPrototype->getTable())->upsert(
                $translationRecords,
                [$translationForeignKey, $localeKey],
                $translationUpdateColumns,
            );
        }

        return $destinationKeys;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function withTimestamps(Model $prototype, array $record): array
    {
        if (! $prototype->usesTimestamps()) {
            return $record;
        }

        $now = now();
        $record['updated_at'] = $record['updated_at'] ?? $now;
        $record['created_at'] = $record['created_at'] ?? $now;

        return $record;
    }

    /**
     * @param  class-string<Model>  $destinationClass
     * @param  array<string, mixed>  $destinationMatch
     */
    private function resolveDestinationKey(string $destinationClass, array $destinationMatch): string
    {
        /** @var Model $prototype */
        $prototype = new $destinationClass;
        $keyName = $prototype->getKeyName();

        $query = $destinationClass::query()->select($keyName);
        foreach ($destinationMatch as $field => $value) {
            $query->where($field, $value);
        }

        $key = $query->value($keyName);

        return $key === null ? '' : (string) $key;
    }

    /**
     * @param  array<string, mixed>  $destinationMatch
     * @param  list<string>  $uniqueBy
     */
    private function recordKey(array $destinationMatch, array $uniqueBy): string
    {
        $key = [];

        foreach ($uniqueBy as $column) {
            $key[$column] = $destinationMatch[$column] ?? null;
        }

        return json_encode($key, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: md5(serialize($key));
    }

    /**
     * @return list<string>
     */
    private function resolveTranslatedAttributes(Model $destination): array
    {
        if (method_exists($destination, 'getTranslatedAttributes')) {
            $attributes = $destination->getTranslatedAttributes();

            return is_array($attributes) ? array_values($attributes) : [];
        }

        if (property_exists($destination, 'translatedAttributes') && is_array($destination->translatedAttributes ?? null)) {
            return array_values($destination->translatedAttributes);
        }

        return [];
    }
}
