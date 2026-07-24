<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Execution;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\Exceptions\TransformDestinationConflictException;
use Moox\Transform\Support\Operations\InlineOperationRegistry;
use Moox\Transform\Support\SourceContextResolver;

final class ResolvedTransformDataFactory
{
    /** @var array<string, mixed> */
    private array $expressionMemo = [];

    public function __construct(
        private readonly InlineOperationRegistry $inlineOperationRegistry,
        private readonly SourceContextResolver $sourceContextResolver,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function make(
        TransformDefinition $definition,
        array $payload,
        string $inputHash,
        ?TransformRecord $record = null,
    ): ResolvedTransformRow {
        $this->expressionMemo = [];

        $resolved = $this->resolveMappedData($definition, $payload);
        $resolvedData = $resolved['data'];
        $warnings = $resolved['warnings'];

        /** @var class-string<Model> $destinationClass */
        $destinationClass = $definition->destination_model;
        /** @var Model $prototype */
        $prototype = new $destinationClass;
        $sourceContext = $this->sourceContextResolver->resolve(
            $this->resolveArrayAttribute($definition, 'source_references'),
            $record instanceof TransformRecord
                ? $this->resolveArrayAttribute($record, 'source_references')
                : [],
            $payload,
        );
        $sourceContext = $this->appendProjectionSourceReferences($definition, $payload, $sourceContext);
        $destinationMatch = $this->resolveDestinationMatchData(
            $definition,
            $payload,
            $prototype,
            $destinationClass,
            $sourceContext,
            $record?->getKey() !== null ? (int) $record->getKey() : 0,
            (string) $definition->name,
        );

        $this->expressionMemo = [];

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
            $value = $this->resolveMappedValueMemoized($payload, $sourcePath, (string) $destinationField, $warnings);
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
    private function resolveMappedValueMemoized(
        array $payload,
        string $sourceExpression,
        string $destinationField,
        array &$warnings,
    ): mixed {
        if (array_key_exists($sourceExpression, $this->expressionMemo)) {
            return $this->expressionMemo[$sourceExpression];
        }

        return $this->expressionMemo[$sourceExpression] = $this->resolveMappedValue(
            $payload,
            $sourceExpression,
            $destinationField,
            $warnings,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $warnings
     */
    private function resolveMappedValue(array $payload, string $sourceExpression, string $destinationField, array &$warnings): mixed
    {
        if ($this->inlineOperationRegistry->isPayloadBaseExpression($sourceExpression)) {
            return $this->inlineOperationRegistry->applyOperation(
                $sourceExpression,
                null,
                $destinationField,
                $warnings,
                $payload,
            );
        }

        $segments = array_values(array_filter(array_map('trim', explode('|', $sourceExpression))));
        if ($segments === []) {
            return null;
        }

        $baseSegment = array_shift($segments);
        if (! is_string($baseSegment) || $baseSegment === '') {
            return null;
        }

        if ($this->inlineOperationRegistry->isPayloadBaseExpression($baseSegment)) {
            $value = $this->inlineOperationRegistry->applyOperation(
                $baseSegment,
                null,
                $destinationField,
                $warnings,
                $payload,
            );
        } else {
            $value = $this->resolveSourceBaseValue($payload, $baseSegment, $destinationField, $warnings);
        }

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
        if ($this->inlineOperationRegistry->isPayloadBaseExpression($sourceExpression)) {
            return $this->inlineOperationRegistry->payloadBaseExpressionExists($payload, $sourceExpression);
        }

        $segments = array_values(array_filter(array_map('trim', explode('|', $sourceExpression))));
        if ($segments === []) {
            return false;
        }

        $baseSegment = array_shift($segments);
        if (! is_string($baseSegment) || $baseSegment === '') {
            return false;
        }

        return $this->inlineOperationRegistry->isPayloadBaseExpression($baseSegment)
            ? $this->inlineOperationRegistry->payloadBaseExpressionExists($payload, $baseSegment)
            : Arr::has($payload, $baseSegment);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{references: list<array<string, mixed>>, primary_source_id: string|int|null}  $sourceContext
     * @return array{references: list<array<string, mixed>>, primary_source_id: string|int|null}
     */
    private function appendProjectionSourceReferences(
        TransformDefinition $definition,
        array $payload,
        array $sourceContext,
    ): array {
        if (($sourceContext['primary_source_id'] ?? null) !== null) {
            return $sourceContext;
        }

        $warnings = [];
        foreach ($this->resolveArrayAttribute($definition, 'destination_match') as $destinationField => $sourcePath) {
            if (! is_string($destinationField) || $destinationField === '' || ! is_string($sourcePath) || $sourcePath === '') {
                continue;
            }

            $sourceId = $this->resolveMappedValueMemoized($payload, $sourcePath, $destinationField, $warnings);
            if ($this->isMissingDestinationMatchValue($sourceId)) {
                continue;
            }

            $sourceContext['references'][] = [
                'source_type' => 'projection',
                'source_path' => $sourcePath,
                'source_id' => $sourceId,
            ];
            $sourceContext['primary_source_id'] = $sourceId;

            break;
        }

        return $sourceContext;
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

            $value = $this->resolveMappedValueMemoized($payload, $sourcePath, $destinationField, $warnings);
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

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveArrayAttribute(Model $model, string $attribute): array
    {
        $value = $model->getAttribute($attribute);

        return is_array($value) ? $value : [];
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
}
