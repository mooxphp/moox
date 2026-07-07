<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Execution;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\Exceptions\TransformDestinationConflictException;
use Moox\Transform\Support\Operations\InlineOperationRegistry;

final class ResolvedTransformDataFactory
{
    public function __construct(
        private readonly InlineOperationRegistry $inlineOperationRegistry,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function make(
        TransformDefinition $definition,
        array $payload,
        string $inputHash,
        ?TransformRecord $record = null,
    ): ResolvedTransformRow {
        $resolved = $this->resolveMappedData($definition, $payload);
        $resolvedData = $resolved['data'];
        $warnings = $resolved['warnings'];

        /** @var class-string<Model> $destinationClass */
        $destinationClass = $definition->destination_model;
        /** @var Model $prototype */
        $prototype = new $destinationClass;
        $sourceContext = $this->resolveSourceContext($record, $definition, $payload);
        $destinationMatch = $this->resolveDestinationMatchData(
            $definition,
            $payload,
            $prototype,
            $destinationClass,
            $sourceContext,
            $record?->getKey() !== null ? (int) $record->getKey() : 0,
            (string) $definition->name,
        );

        return new ResolvedTransformRow(
            destinationClass: $destinationClass,
            payload: $payload,
            resolvedData: $this->normalizeResolvedDataForDestinationCasts($prototype, $resolvedData),
            destinationMatch: $destinationMatch,
            sourceContext: $sourceContext,
            inputHash: $inputHash,
            warnings: $warnings,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{data: array<string, mixed>, warnings: array<int, string>}
     */
    private function resolveMappedData(TransformDefinition $definition, array $payload): array
    {
        $mapping = $this->resolveArrayAttribute($definition, 'field_map');
        if ($mapping === []) {
            return [
                'data' => $payload,
                'warnings' => [],
            ];
        }

        $resolved = [];
        $warnings = [];

        foreach ($mapping as $destinationField => $sourcePath) {
            if (! is_string($sourcePath) || $sourcePath === '') {
                $warnings[] = "Invalid mapping for {$destinationField}.";

                continue;
            }

            $pathExists = $this->sourceExpressionPathExists($payload, $sourcePath);
            $value = $this->resolveMappedValue($payload, $sourcePath, (string) $destinationField, $warnings);
            if (! $pathExists) {
                $warnings[] = "Mapped source path [{$sourcePath}] was not found.";
            }

            $resolved[(string) $destinationField] = $value;
        }

        return [
            'data' => $resolved,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $warnings
     */
    private function resolveMappedValue(array $payload, string $sourceExpression, string $destinationField, array &$warnings): mixed
    {
        $segments = array_values(array_filter(array_map('trim', explode('|', $sourceExpression))));
        if ($segments === []) {
            return null;
        }

        $basePath = array_shift($segments);
        if (! is_string($basePath) || $basePath === '') {
            return null;
        }

        $value = $this->resolveSourceBaseValue($payload, $basePath, $destinationField, $warnings);

        foreach ($segments as $operation) {
            $value = $this->inlineOperationRegistry->applyOperation(
                $operation,
                $value,
                $destinationField,
                $warnings,
                $payload,
            );
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $warnings
     */
    private function resolveSourceBaseValue(
        array $payload,
        string $basePath,
        string $destinationField,
        array &$warnings,
    ): mixed {
        if ($this->inlineOperationRegistry->isPayloadBaseExpression($basePath)) {
            return $this->inlineOperationRegistry->applyOperation(
                $basePath,
                null,
                $destinationField,
                $warnings,
                $payload,
            );
        }

        return Arr::get($payload, $basePath);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sourceExpressionPathExists(array $payload, string $sourceExpression): bool
    {
        $segments = array_values(array_filter(array_map('trim', explode('|', $sourceExpression))));
        if ($segments === []) {
            return false;
        }

        $basePath = array_shift($segments);
        if (! is_string($basePath) || $basePath === '') {
            return false;
        }

        return $this->inlineOperationRegistry->isPayloadBaseExpression($basePath)
            ? $this->inlineOperationRegistry->payloadBaseExpressionExists($payload, $basePath)
            : Arr::has($payload, $basePath);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{references: list<array<string, mixed>>, primary_source_id: string|int|null}
     */
    private function resolveSourceContext(?TransformRecord $record, TransformDefinition $definition, array $payload): array
    {
        $definitionReferences = $this->resolveArrayAttribute($definition, 'source_references');
        $runtimeReferences = $record instanceof TransformRecord
            ? $this->resolveArrayAttribute($record, 'source_references')
            : [];
        $references = $runtimeReferences !== [] ? $runtimeReferences : $definitionReferences;
        $resolvedReferences = [];

        foreach ($references as $reference) {
            if (! is_array($reference)) {
                continue;
            }

            $sourceType = $reference['source_type'] ?? null;
            if (! is_string($sourceType) || $sourceType === '') {
                continue;
            }

            $sourceId = $reference['row_key'] ?? null;
            if ($sourceId === null && $sourceType === 'db_table') {
                $keyColumn = is_string($reference['key_column'] ?? null) && $reference['key_column'] !== ''
                    ? $reference['key_column']
                    : 'id';
                $alias = is_string($reference['alias'] ?? null) && $reference['alias'] !== ''
                    ? $reference['alias'].'.'
                    : '';
                $sourceId = Arr::get($payload, $alias.$keyColumn) ?? Arr::get($payload, $keyColumn);
            }

            $resolvedReferences[] = array_filter([
                'source_type' => $sourceType,
                'connection' => $reference['connection'] ?? null,
                'table' => $reference['table'] ?? null,
                'key_column' => $reference['key_column'] ?? null,
                'path' => $reference['path'] ?? null,
                'url' => $reference['url'] ?? null,
                'alias' => $reference['alias'] ?? null,
                'source_id' => $sourceId,
            ], static fn (mixed $value): bool => $value !== null && $value !== '');
        }

        if ($resolvedReferences === []) {
            $warnings = [];
            foreach ($this->resolveArrayAttribute($definition, 'destination_match') as $destinationField => $sourcePath) {
                if (! is_string($destinationField) || $destinationField === '' || ! is_string($sourcePath) || $sourcePath === '') {
                    continue;
                }

                $sourceId = $this->resolveMappedValue($payload, $sourcePath, $destinationField, $warnings);
                if ($this->isMissingDestinationMatchValue($sourceId)) {
                    continue;
                }

                $resolvedReferences[] = [
                    'source_type' => 'projection',
                    'source_path' => $sourcePath,
                    'source_id' => $sourceId,
                ];
            }
        }

        $primarySourceId = null;
        foreach ($resolvedReferences as $reference) {
            if (($reference['source_id'] ?? null) !== null) {
                $primarySourceId = $reference['source_id'];

                break;
            }
        }

        return [
            'references' => $resolvedReferences,
            'primary_source_id' => $primarySourceId,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{references: list<array<string, mixed>>, primary_source_id: string|int|null}  $sourceContext
     * @return array<string, mixed>
     */
    private function resolveDestinationMatchData(
        TransformDefinition $definition,
        array $payload,
        Model $prototype,
        string $destinationClass,
        array $sourceContext,
        int $transformRecordId,
        string $transformDefinitionName,
    ): array {
        $destinationMatch = $this->resolveArrayAttribute($definition, 'destination_match');
        if ($destinationMatch === []) {
            return [];
        }

        $resolved = [];
        $missing = [];
        $warnings = [];

        foreach ($destinationMatch as $destinationField => $sourcePath) {
            if (! is_string($destinationField) || $destinationField === '' || ! is_string($sourcePath) || $sourcePath === '') {
                $missing[] = (string) $destinationField;

                continue;
            }

            $value = $this->resolveMappedValue($payload, $sourcePath, $destinationField, $warnings);
            if ($this->isMissingDestinationMatchValue($value)) {
                $missing[] = "{$destinationField} (from {$sourcePath})";

                continue;
            }

            $resolved[$destinationField] = $this->normalizeDestinationMatchValue($prototype, $destinationField, $value);
        }

        if ($missing !== []) {
            throw TransformDestinationConflictException::incompleteDestinationMatch(
                $missing,
                $destinationMatch,
                $sourceContext,
                $destinationClass,
                $transformRecordId,
                $transformDefinitionName,
            );
        }

        return $resolved;
    }

    private function normalizeDestinationMatchValue(Model $prototype, string $field, mixed $value): mixed
    {
        $castType = strtolower(explode(':', (string) ($prototype->getCasts()[$field] ?? ''))[0]);

        return match ($castType) {
            'int', 'integer' => (int) $value,
            'float', 'double', 'decimal', 'real' => (float) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array', 'json', 'collection', 'object' => $value,
            default => is_scalar($value) ? (string) $value : $value,
        };
    }

    private function isMissingDestinationMatchValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        return is_string($value) && trim($value) === '';
    }

    /**
     * @param  array<string, mixed>  $resolvedData
     * @return array<string, mixed>
     */
    private function normalizeResolvedDataForDestinationCasts(Model $prototype, array $resolvedData): array
    {
        $casts = $prototype->getCasts();

        foreach ($resolvedData as $field => $value) {
            if (! is_string($value)) {
                continue;
            }

            $castType = strtolower((string) ($casts[$field] ?? ''));
            $castType = explode(':', $castType)[0];
            if (! in_array($castType, ['array', 'json', 'collection', 'object'], true)) {
                continue;
            }

            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                continue;
            }

            $resolvedData[$field] = $decoded;
        }

        return $resolvedData;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveArrayAttribute(Model $model, string $attribute): array
    {
        $value = $model->getAttribute($attribute);

        return is_array($value) ? $value : [];
    }
}
