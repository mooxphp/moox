<?php

declare(strict_types=1);

use Moox\Transform\Models\TransformRecord;
use Tests\TestCase;

require_once dirname(__DIR__, 2).'/Support/TransformTestSupport.php';

uses(TestCase::class);

test('it writes translated fields for draft-like destination model', function (): void {
    createTestTables();
    $definition = createDefinition([
        'destination_model' => TransformDraftMainModel::class,
        'field_map' => [
            'title' => 'legacy.title',
            'status' => 'legacy.status',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'title' => 'Translated Title',
                'status' => 'active',
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('processed');
    expect($record->validation_status)->toBe('valid');

    $saved = TransformDraftMainModel::query()->first();
    expect($saved)->not()->toBeNull();
    expect($saved?->status)->toBe('draft');

    $locale = (string) config('transform.default_locale', app()->getLocale());
    $translation = TransformDraftMainTranslationModel::query()
        ->where('transform_draft_main_model_id', $saved?->id)
        ->where('locale', $locale)
        ->first();

    expect($translation)->not()->toBeNull();
    expect($translation?->title)->toBe('Translated Title');
});
