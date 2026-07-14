<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

final class ImportRecordSelectOptionBuilder
{
    /**
     * @param  class-string<Model>  $importRecordModel
     */
    public function __construct(
        private readonly string $importRecordModel,
        private readonly ?string $endpointRelation = null,
        private readonly int $limit = 100,
    ) {}

    public static function fromConfig(): ?self
    {
        $importRecordModel = config('transform.import_record_model');
        if (! is_string($importRecordModel) || $importRecordModel === '' || ! class_exists($importRecordModel) || ! is_subclass_of($importRecordModel, Model::class)) {
            return null;
        }

        $endpointRelation = config('transform.import_record_endpoint_relation');
        $limit = config('transform.import_record_select_limit', 100);

        return new self(
            importRecordModel: $importRecordModel,
            endpointRelation: is_string($endpointRelation) && $endpointRelation !== '' ? $endpointRelation : null,
            limit: is_int($limit) && $limit > 0 ? $limit : 100,
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function groupedOptions(?string $search = null): array
    {
        $records = $this->queryRecords($search)->get();
        $grouped = [];

        foreach ($records as $record) {
            if (! $record instanceof Model) {
                continue;
            }

            $groupLabel = self::formatEndpointGroupLabel($this->resolveEndpoint($record));
            $grouped[$groupLabel] ??= [];
            $grouped[$groupLabel][(int) $record->getKey()] = self::formatRecordOptionLabel($record);
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * @return array<int, string>
     */
    public function endpointSelectOptions(): array
    {
        $foreignKey = self::stringConfig('transform.import_record_endpoint_foreign_key', 'api_endpoint_id');
        /** @var Model $prototype */
        $prototype = new $this->importRecordModel;

        if ($foreignKey === '' || ! $prototype->isFillable($foreignKey)) {
            return [];
        }

        $endpointIds = $prototype->newQuery()
            ->whereNotNull($foreignKey)
            ->distinct()
            ->orderBy($foreignKey)
            ->pluck($foreignKey);

        $options = [];

        foreach ($endpointIds as $endpointId) {
            if (! is_numeric($endpointId)) {
                continue;
            }

            $endpointId = (int) $endpointId;
            $sampleRecord = $prototype->newQuery()
                ->where($foreignKey, $endpointId)
                ->with(array_filter([$this->resolveEndpointRelationName($prototype)]))
                ->orderByDesc($prototype->getKeyName())
                ->first();

            if (! $sampleRecord instanceof Model) {
                $options[$endpointId] = (string) __('transform::fields.import_record_group_endpoint', ['id' => $endpointId]);

                continue;
            }

            $options[$endpointId] = self::formatEndpointGroupLabel($this->resolveEndpoint($sampleRecord));
        }

        return $options;
    }

    public function labelForId(int $importRecordId): ?string
    {
        if ($importRecordId <= 0) {
            return null;
        }

        $record = $this->queryRecords(null)
            ->whereKey($importRecordId)
            ->first();

        if (! $record instanceof Model) {
            return null;
        }

        $endpoint = $this->resolveEndpoint($record);
        $groupLabel = self::formatEndpointGroupLabel($endpoint);

        return $groupLabel.' → '.self::formatRecordOptionLabel($record);
    }

    public static function formatEndpointGroupLabel(?Model $endpoint): string
    {
        if (! $endpoint instanceof Model) {
            return (string) __('transform::fields.import_record_group_unknown');
        }

        $labelColumns = self::stringListConfig('transform.import_record_endpoint_label_columns', ['name', 'method', 'path']);
        $values = [];

        foreach ($labelColumns as $column) {
            $value = $endpoint->getAttribute($column);
            if (! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }

            $values[$column] = $column === 'method'
                ? strtoupper(trim((string) $value))
                : trim((string) $value);
        }

        $name = $values['name'] ?? '';
        $method = $values['method'] ?? '';
        $path = $values['path'] ?? '';
        $route = trim($method !== '' ? "{$method} {$path}" : $path);

        if ($name !== '' && $route !== '') {
            return "{$name} — {$route}";
        }

        if ($name !== '') {
            return $name;
        }

        if ($route !== '') {
            return $route;
        }

        $parts = array_values(array_filter($values, static fn (string $value): bool => $value !== ''));
        if ($parts !== []) {
            return implode(' — ', $parts);
        }

        $endpointId = $endpoint->getKey();

        return $endpointId !== null
            ? (string) __('transform::fields.import_record_group_endpoint', ['id' => $endpointId])
            : (string) __('transform::fields.import_record_group_unknown');
    }

    public static function formatRecordOptionLabel(Model $record): string
    {
        $keyColumn = self::stringConfig('transform.import_record_key_column', 'external_key');
        $keyValue = $record->getAttribute($keyColumn);
        $keyPart = is_string($keyValue) && trim($keyValue) !== ''
            ? trim($keyValue)
            : (string) __('transform::fields.import_record_full_payload');

        $label = sprintf('#%s · %s', $record->getKey(), $keyPart);

        foreach (self::stringListConfig('transform.import_record_meta_columns', ['status', 'updated_at']) as $column) {
            $metaPart = self::formatMetaColumnValue($record->getAttribute($column));
            if ($metaPart !== '') {
                $label .= ' · '.$metaPart;
            }
        }

        return $label;
    }

    /**
     * @return Builder<Model>
     */
    private function queryRecords(?string $search)
    {
        /** @var Model $prototype */
        $prototype = new $this->importRecordModel;
        $relation = $this->resolveEndpointRelationName($prototype);
        $keyColumn = self::stringConfig('transform.import_record_key_column', 'external_key');
        $endpointSearchColumns = self::stringListConfig('transform.import_record_endpoint_search_columns', ['name', 'path', 'method']);

        $query = $prototype->newQuery()->orderByDesc($prototype->getKeyName());

        if ($relation !== null) {
            $query->with([$relation]);
        }

        $search = trim((string) $search);
        if ($search !== '') {
            $query->where(function ($inner) use ($search, $relation, $prototype, $keyColumn, $endpointSearchColumns): void {
                if (ctype_digit($search)) {
                    $inner->orWhere($prototype->getKeyName(), (int) $search);
                }

                if ($keyColumn !== '' && ($prototype->isFillable($keyColumn) || array_key_exists($keyColumn, $prototype->getAttributes()))) {
                    $inner->orWhere($keyColumn, 'like', '%'.$search.'%');
                }

                if ($relation !== null && $endpointSearchColumns !== []) {
                    $inner->orWhereHas($relation, function ($endpointQuery) use ($search, $endpointSearchColumns): void {
                        foreach ($endpointSearchColumns as $index => $column) {
                            if ($index === 0) {
                                $endpointQuery->where($column, 'like', '%'.$search.'%');

                                continue;
                            }

                            $endpointQuery->orWhere($column, 'like', '%'.$search.'%');
                        }
                    });
                }
            });
        }

        return $query->limit($this->limit);
    }

    private function resolveEndpointRelationName(Model $prototype): ?string
    {
        if ($this->endpointRelation !== null && method_exists($prototype, $this->endpointRelation)) {
            $relation = $prototype->{$this->endpointRelation}();
            if ($relation instanceof Relation) {
                return $this->endpointRelation;
            }
        }

        foreach (self::stringListConfig('transform.import_record_endpoint_relation_candidates', ['apiEndpoint', 'endpoint']) as $candidate) {
            if (! method_exists($prototype, $candidate)) {
                continue;
            }

            $relation = $prototype->{$candidate}();
            if ($relation instanceof Relation) {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveEndpoint(Model $record): ?Model
    {
        $relation = $this->resolveEndpointRelationName($record);

        if ($relation === null) {
            return null;
        }

        $endpoint = $record->getRelationValue($relation);

        return $endpoint instanceof Model ? $endpoint : null;
    }

    private static function formatMetaColumnValue(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i');
        }

        if (is_string($value)) {
            return trim($value);
        }

        return '';
    }

    private static function hasConfig(): bool
    {
        if (! function_exists('config') || ! function_exists('app')) {
            return false;
        }

        try {
            return app()->bound('config');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  list<string>  $default
     * @return list<string>
     */
    private static function stringListConfig(string $key, array $default): array
    {
        if (! self::hasConfig()) {
            return $default;
        }

        $value = config($key, $default);

        if (! is_array($value)) {
            return $default;
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => is_string($item) ? trim($item) : '',
            $value,
        ), static fn (string $item): bool => $item !== ''));
    }

    private static function stringConfig(string $key, string $default): string
    {
        if (! self::hasConfig()) {
            return $default;
        }

        $value = config($key, $default);

        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }
}
