<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

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

        $name = trim((string) ($endpoint->getAttribute('name') ?? ''));
        $method = strtoupper(trim((string) ($endpoint->getAttribute('method') ?? '')));
        $path = trim((string) ($endpoint->getAttribute('path') ?? ''));
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

        $endpointId = $endpoint->getKey();

        return $endpointId !== null
            ? (string) __('transform::fields.import_record_group_endpoint', ['id' => $endpointId])
            : (string) __('transform::fields.import_record_group_unknown');
    }

    public static function formatRecordOptionLabel(Model $record): string
    {
        $externalKey = $record->getAttribute('external_key');
        $keyPart = is_string($externalKey) && $externalKey !== ''
            ? $externalKey
            : (string) __('transform::fields.import_record_full_payload');

        $status = trim((string) ($record->getAttribute('status') ?? ''));
        $updatedAt = $record->getAttribute('updated_at');
        $updatedPart = $updatedAt instanceof Carbon
            ? $updatedAt->format('Y-m-d H:i')
            : (is_string($updatedAt) && $updatedAt !== '' ? $updatedAt : '');

        $label = sprintf('#%s · %s', $record->getKey(), $keyPart);

        if ($status !== '') {
            $label .= ' · '.$status;
        }

        if ($updatedPart !== '') {
            $label .= ' · '.$updatedPart;
        }

        return $label;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Model>
     */
    private function queryRecords(?string $search)
    {
        /** @var Model $prototype */
        $prototype = new $this->importRecordModel;
        $relation = $this->resolveEndpointRelationName($prototype);

        $query = $prototype->newQuery()->orderByDesc($prototype->getKeyName());

        if ($relation !== null) {
            $query->with([$relation]);
        }

        $search = trim((string) $search);
        if ($search !== '') {
            $query->where(function ($inner) use ($search, $relation, $prototype): void {
                if (ctype_digit($search)) {
                    $inner->orWhere($prototype->getKeyName(), (int) $search);
                }

                if ($prototype->isFillable('external_key') || array_key_exists('external_key', $prototype->getAttributes())) {
                    $inner->orWhere('external_key', 'like', '%'.$search.'%');
                }

                if ($relation !== null) {
                    $inner->orWhereHas($relation, function ($endpointQuery) use ($search): void {
                        $endpointQuery
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('path', 'like', '%'.$search.'%')
                            ->orWhere('method', 'like', '%'.$search.'%');
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

        foreach (['apiEndpoint', 'endpoint'] as $candidate) {
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
}
