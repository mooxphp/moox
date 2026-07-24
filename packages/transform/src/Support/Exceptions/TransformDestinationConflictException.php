<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Exceptions;

use Moox\Transform\Support\SourceContextResolver;
use RuntimeException;

final class TransformDestinationConflictException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        private readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    public function isIncompleteDestinationMatch(): bool
    {
        return ($this->context['type'] ?? null) === 'incomplete_destination_match';
    }

    /**
     * @param  array<string, mixed>  $destinationMatch
     * @param  list<string|int>  $existingDestinationKeys
     * @param  array<string, mixed>  $sourceContext
     */
    public static function multipleMatches(
        string $destinationModel,
        array $destinationMatch,
        array $existingDestinationKeys,
        array $sourceContext,
        ?int $transformRecordId = null,
        ?string $transformDefinitionName = null,
    ): self {
        $existingKeys = array_values(array_map('strval', $existingDestinationKeys));

        $message = sprintf(
            'Destination conflict: %d existing %s records match destination_match. destination_match=%s. existing_destination_keys=[%s]. source=%s.',
            count($existingKeys),
            class_basename($destinationModel),
            self::encodeJson($destinationMatch),
            implode(', ', $existingKeys),
            app(SourceContextResolver::class)->formatSummary($sourceContext),
        );

        return new self($message, self::baseContext(
            type: 'multiple_destination_matches',
            destinationModel: $destinationModel,
            destinationMatch: $destinationMatch,
            existingDestinationKeys: $existingKeys,
            sourceContext: $sourceContext,
            transformRecordId: $transformRecordId,
            transformDefinitionName: $transformDefinitionName,
        ));
    }

    /**
     * @param  array<string, mixed>  $destinationMatch
     * @param  array<string, mixed>  $sourceContext
     */
    public static function uniqueConstraintViolation(
        string $destinationModel,
        array $destinationMatch,
        array $sourceContext,
        ?string $existingDestinationKey,
        string $databaseMessage,
        ?int $transformRecordId = null,
        ?string $transformDefinitionName = null,
    ): self {
        $message = sprintf(
            'Destination conflict: unique constraint violated while saving %s. destination_match=%s. existing_destination_key=%s. source=%s. database_error=%s',
            class_basename($destinationModel),
            self::encodeJson($destinationMatch),
            $existingDestinationKey ?? 'unknown',
            app(SourceContextResolver::class)->formatSummary($sourceContext),
            $databaseMessage,
        );

        return new self($message, self::baseContext(
            type: 'unique_constraint_violation',
            destinationModel: $destinationModel,
            destinationMatch: $destinationMatch,
            existingDestinationKeys: $existingDestinationKey !== null ? [$existingDestinationKey] : [],
            sourceContext: $sourceContext,
            transformRecordId: $transformRecordId,
            transformDefinitionName: $transformDefinitionName,
            extra: [
                'database_error' => $databaseMessage,
            ],
        ));
    }

    /**
     * @param  array<string, mixed>  $destinationMatch
     * @param  array<string, mixed>  $sourceContext
     */
    public static function incompleteDestinationMatch(
        array $missingFields,
        array $destinationMatch,
        array $sourceContext,
        string $destinationModel,
        ?int $transformRecordId = null,
        ?string $transformDefinitionName = null,
    ): self {
        $message = sprintf(
            'Destination conflict: destination_match could not be fully resolved for %s. missing=%s. configured_match=%s. source=%s.',
            class_basename($destinationModel),
            implode(', ', $missingFields),
            self::encodeJson($destinationMatch),
            app(SourceContextResolver::class)->formatSummary($sourceContext),
        );

        return new self($message, self::baseContext(
            type: 'incomplete_destination_match',
            destinationModel: $destinationModel,
            destinationMatch: $destinationMatch,
            existingDestinationKeys: [],
            sourceContext: $sourceContext,
            transformRecordId: $transformRecordId,
            transformDefinitionName: $transformDefinitionName,
            extra: [
                'missing_fields' => $missingFields,
            ],
        ));
    }

    /**
     * @param  array<string, mixed>  $destinationMatch
     * @param  list<string>  $existingDestinationKeys
     * @param  array<string, mixed>  $sourceContext
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function baseContext(
        string $type,
        string $destinationModel,
        array $destinationMatch,
        array $existingDestinationKeys,
        array $sourceContext,
        ?int $transformRecordId = null,
        ?string $transformDefinitionName = null,
        array $extra = [],
    ): array {
        return array_merge([
            'type' => $type,
            'destination_model' => $destinationModel,
            'destination_match' => $destinationMatch,
            'existing_destination_keys' => $existingDestinationKeys,
            'source' => $sourceContext,
            'transform_record_id' => $transformRecordId,
            'transform_definition' => $transformDefinitionName,
        ], $extra);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function encodeJson(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
