<?php

declare(strict_types=1);

namespace Moox\Transform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Moox\Core\Entities\Items\Record\BaseRecordModel;

class TransformRecord extends BaseRecordModel
{
    protected $table = 'transform_records';

    protected $fillable = [
        'transform_definition_id',
        'parent_transform_record_id',
        'destination_key',
        'source_projection',
        'source_references',
        'input_hash',
        'status',
        'validation_status',
        'validation_errors',
        'warnings',
        'attempts',
        'degraded',
        'bulk_stats',
        'last_run_at',
        'last_success_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'source_projection' => 'array',
            'source_references' => 'array',
            'validation_errors' => 'array',
            'warnings' => 'array',
            'bulk_stats' => 'array',
            'degraded' => 'boolean',
            'last_run_at' => 'datetime',
            'last_success_at' => 'datetime',
        ];
    }

    public static function getResourceName(): string
    {
        return 'transform-record';
    }

    protected static function booted(): void
    {
        static::saving(function (self $record): void {
            if (! $record->isDirty(['transform_definition_id', 'source_projection', 'source_references'])) {
                return;
            }

            $errors = [];

            $definition = $record->definition()->first();
            if (! $definition instanceof TransformDefinition) {
                $errors['transform_definition_id'][] = 'A valid transform definition is required.';
            }

            $projectionValue = $record->getAttribute('source_projection');
            $runtimeReferencesValue = $record->getAttribute('source_references');
            $definitionReferencesValue = $definition instanceof TransformDefinition
                ? $definition->getAttribute('source_references')
                : null;

            $projection = is_array($projectionValue) ? $projectionValue : [];
            $runtimeReferences = is_array($runtimeReferencesValue) ? $runtimeReferencesValue : [];
            $definitionReferences = is_array($definitionReferencesValue)
                ? $definitionReferencesValue
                : [];

            if ($projection === [] && $runtimeReferences === [] && $definitionReferences === []) {
                $errors['source'][] = 'A transform requires source_projection or source_references.';
            }

            if ($runtimeReferences !== []) {
                $errors = array_merge_recursive($errors, self::validateRuntimeSourceReferences($runtimeReferences));
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
        });
    }

    /**
     * Keep runtime validation lightweight for queued processing records.
     *
     * @param  array<int, mixed>  $references
     * @return array<string, array<int, string>>
     */
    private static function validateRuntimeSourceReferences(array $references): array
    {
        $errors = [];

        foreach ($references as $index => $reference) {
            if (! is_array($reference)) {
                $errors["source_references.{$index}"][] = 'Each source reference must be an object/array.';

                continue;
            }

            $sourceType = $reference['source_type'] ?? null;
            if (! is_string($sourceType) || $sourceType === '') {
                $errors["source_references.{$index}.source_type"][] = 'source_type is required.';
            }
        }

        return $errors;
    }

    /**
     * @return BelongsTo<TransformDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(TransformDefinition::class, 'transform_definition_id');
    }

    /**
     * @return BelongsTo<TransformRecord, $this>
     */
    public function parentRecord(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_transform_record_id');
    }

    /**
     * @return HasMany<TransformRecord, $this>
     */
    public function childRecords(): HasMany
    {
        return $this->hasMany(self::class, 'parent_transform_record_id');
    }
}
