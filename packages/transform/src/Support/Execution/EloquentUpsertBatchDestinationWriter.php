<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Execution;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moox\Transform\Contracts\BatchDestinationWriter;
use Moox\Transform\Models\TransformDefinition;

final class EloquentUpsertBatchDestinationWriter implements BatchDestinationWriter
{
    public function supports(string $destinationClass, TransformDefinition $definition): bool
    {
        return $this->resolveTranslatedAttributes(new $destinationClass) === [];
    }

    public function write(string $destinationClass, TransformDefinition $definition, array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        /** @var Model $prototype */
        $prototype = new $destinationClass;
        $table = $prototype->getTable();
        $fillable = $prototype->getFillable();
        $allowed = $fillable !== [] ? array_flip($fillable) : null;
        $recordsByKey = [];
        $uniqueBy = array_keys($rows[0]->destinationMatch);

        foreach ($rows as $row) {
            $record = [];
            foreach ($row->resolvedData as $field => $value) {
                if (is_array($allowed) && ! array_key_exists($field, $allowed)) {
                    continue;
                }

                $record[$field] = $value;
            }

            if ($record === []) {
                continue;
            }

            $recordsByKey[$this->recordKey($row->destinationMatch, $uniqueBy)] = $this->withTimestamps(
                $prototype,
                $this->encodeJsonCastAttributes($prototype, $record),
            );
        }

        $records = array_values($recordsByKey);

        if ($records === []) {
            return array_fill(0, count($rows), '');
        }

        $updateColumns = array_values(array_diff(array_keys($records[0]), $uniqueBy, ['created_at']));

        DB::table($table)->upsert($records, $uniqueBy, $updateColumns);

        return array_map(
            fn (ResolvedTransformRow $row): string => $this->resolveDestinationKey($destinationClass, $row->destinationMatch),
            $rows,
        );
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
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function encodeJsonCastAttributes(Model $prototype, array $record): array
    {
        $casts = $prototype->getCasts();

        foreach ($record as $field => $value) {
            if (! is_array($value)) {
                continue;
            }

            $castType = strtolower((string) ($casts[$field] ?? ''));
            $castType = explode(':', $castType)[0];
            if (! in_array($castType, ['array', 'json', 'collection', 'object'], true)) {
                continue;
            }

            $record[$field] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $record;
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
