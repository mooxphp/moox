<?php

declare(strict_types=1);

use Moox\Audit\Models\Activity;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\Audit\Tests\Support\TestAuditableItem;
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
    $this->registerTestAuditableModel();

    $item = TestAuditableItem::query()->create([
        'title' => 'A',
        'status' => 'draft',
    ]);

    expect(Activity::query()->where('event', 'created')->count())->toBe(1);
});
