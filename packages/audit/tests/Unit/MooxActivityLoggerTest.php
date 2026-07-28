<?php

declare(strict_types=1);

use Moox\Audit\Models\Activity;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\Audit\Support\CustomFieldAuditMerger;
use Moox\Audit\Tests\Support\TestAuditableItem;
use Moox\Audit\Tests\Support\TestNonSoftDeleteAuditableItem;
use Moox\Audit\Tests\TestCase;

uses(TestCase::class);

it('logs system events with entry_type log', function (): void {
    MooxActivityLogger::log('system', 'Test system event', [
        'entry_type' => 'log',
        'properties' => ['source' => 'test'],
    ]);

    $activity = Activity::query()->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('system')
        ->and($activity->entry_type)->toBe('log')
        ->and($activity->description)->toBe('Test system event');
});

it('records model updates via audit integration', function (): void {
    /** @var TestCase $this */
    $this->registerTestAuditableModel();

    $item = TestAuditableItem::query()->create([
        'title' => 'Original',
        'status' => 'draft',
        'scope' => 'category:draft:default:private',
    ]);

    $item->update(['title' => 'Updated']);

    $activity = Activity::query()
        ->where('event', 'updated')
        ->where('subject_type', TestAuditableItem::class)
        ->where('subject_id', $item->getKey())
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->entry_type)->toBe('audit')
        ->and($activity->log_name)->toBe('test')
        ->and($activity->scope)->toBe('category:draft:default:private')
        ->and($activity->attribute_changes?->get('attributes'))->toHaveKey('title');
});

it('does not log hidden actor attributes on translations pattern', function (): void {
    /** @var TestCase $this */
    $this->registerTestAuditableModel();

    $item = TestAuditableItem::query()->create([
        'title' => 'A',
        'status' => 'draft',
    ]);

    expect(Activity::query()->where('event', 'created')->count())->toBe(1);
});

it('records custom fields like normal audited fields', function (): void {
    /** @var TestCase $this */
    $this->registerTestAuditableModel();

    $item = TestAuditableItem::query()->create([
        'title' => 'Original',
        'status' => 'draft',
        'builder_payload' => [
            'hero_title' => 'Welcome',
        ],
    ]);

    $item->update([
        'title' => 'Changed title',
    ]);

    $activity = Activity::query()
        ->where('event', 'updated')
        ->where('subject_type', TestAuditableItem::class)
        ->where('subject_id', $item->getKey())
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->attribute_changes?->get('old'))->toMatchArray([
            'title' => 'Original',
        ])
        ->and($activity->attribute_changes?->get('attributes'))->toMatchArray([
            'title' => 'Changed title',
        ]);
});

it('merges custom field changes into the normal audit entry', function (): void {
    /** @var TestCase $this */
    $this->registerTestAuditableModel();

    $item = TestAuditableItem::query()->create([
        'title' => 'Original',
        'status' => 'draft',
        'builder_payload' => [
            'hero_title' => 'Welcome',
        ],
    ]);

    $item->update([
        'title' => 'Changed title',
    ]);

    app()->instance('Moox\\Builder\\Services\\CustomFieldsManager', new class
    {
        /**
         * @return array<string, mixed>
         */
        public function preparedFormValues(string $resourceClass, object $record, array $data): array
        {
            return [
                'hero_title' => 'Updated headline',
                'cta_label' => 'Buy now',
            ];
        }
    });

    app(CustomFieldAuditMerger::class)
        ->mergeUpdated($item, 'TestResource', []);

    $activity = Activity::query()
        ->where('event', 'updated')
        ->where('subject_type', TestAuditableItem::class)
        ->where('subject_id', $item->getKey())
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->attribute_changes?->get('old'))->toMatchArray([
            'title' => 'Original',
            'hero_title' => 'Welcome',
            'cta_label' => null,
        ])
        ->and($activity->attribute_changes?->get('attributes'))->toMatchArray([
            'title' => 'Changed title',
            'hero_title' => 'Updated headline',
            'cta_label' => 'Buy now',
        ]);
});

it('boots non soft delete models without registering restored listeners', function (): void {
    /** @var TestCase $this */
    $this->registerNonSoftDeleteAuditableModel();

    $item = TestNonSoftDeleteAuditableItem::query()->create([
        'title' => 'Simple',
        'status' => 'draft',
    ]);

    $item->update(['title' => 'Updated']);

    expect(Activity::query()
        ->where('subject_type', TestNonSoftDeleteAuditableItem::class)
        ->where('subject_id', $item->getKey())
        ->where('event', 'updated')
        ->exists())->toBeTrue();
});

it('excludes hidden custom fields from audit changes', function (): void {
    /** @var TestCase $this */
    $this->registerTestAuditableModel([
        'hidden_attributes' => ['secret_note'],
    ]);

    $item = TestAuditableItem::query()->create([
        'title' => 'Original',
        'status' => 'draft',
        'builder_payload' => [
            'hero_title' => 'Visible',
            'secret_note' => 'Do not log',
        ],
    ]);

    $item->update([
        'title' => 'Changed title',
    ]);

    app()->instance('Moox\\Builder\\Services\\CustomFieldsManager', new class
    {
        /**
         * @return array<string, mixed>
         */
        public function preparedFormValues(string $resourceClass, object $record, array $data): array
        {
            return [
                'hero_title' => 'Updated visible',
                'secret_note' => 'Still hidden',
            ];
        }
    });

    app(CustomFieldAuditMerger::class)
        ->mergeUpdated($item, 'TestResource', []);

    $activity = Activity::query()
        ->where('event', 'updated')
        ->where('subject_type', TestAuditableItem::class)
        ->where('subject_id', $item->getKey())
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->attribute_changes?->get('old'))->not->toHaveKey('secret_note')
        ->and($activity->attribute_changes?->get('attributes'))->not->toHaveKey('secret_note')
        ->and($activity->attribute_changes?->get('attributes'))->toMatchArray([
            'title' => 'Changed title',
            'hero_title' => 'Updated visible',
        ]);
});
