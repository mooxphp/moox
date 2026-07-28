<?php

declare(strict_types=1);

use Moox\Audit\Models\Activity;
use Moox\Audit\Support\ActivityEntryPresenter;
use Moox\Audit\Tests\Support\TestAuditableItem;
use Moox\Audit\Tests\TestCase;

uses(TestCase::class);

it('flattens nested attribute changes for display', function (): void {
    $result = ActivityEntryPresenter::flattenChanges([
        'old' => ['title' => 'Original', 'status' => 'draft'],
        'attributes' => ['title' => 'Updated', 'status' => 'draft'],
    ]);

    expect($result)->toBe([
        'title' => 'Original → Updated',
    ]);
});

it('formats property values as strings', function (): void {
    $result = ActivityEntryPresenter::flattenProperties([
        'source' => 'test',
        'flags' => ['a', 'b'],
        'enabled' => true,
    ]);

    expect($result['source'])->toBe('test')
        ->and($result['flags'])->toBe('["a","b"]')
        ->and($result['enabled'])->toBe('true');
});

it('formats media-like snapshots as readable labels', function (): void {
    expect(ActivityEntryPresenter::formatValue([
        'id' => 7,
        'alt' => 'Screenshot',
        'title' => 'Hero image',
        'file_name' => 'hero.png',
    ]))->toBe('Hero image (#7)')
        ->and(ActivityEntryPresenter::formatValue([
            'id' => 3,
            'file_name' => 'doc.pdf',
        ]))->toBe('doc.pdf (#3)')
        ->and(ActivityEntryPresenter::formatValue(['id' => 9]))->toBe('#9');
});

it('formats gallery media snapshots as readable labels', function (): void {
    expect(ActivityEntryPresenter::formatValue([
        '1' => [
            'id' => 5,
            'alt' => 'Screenshot',
            'title' => 'First image',
            'file_name' => 'a.png',
        ],
        '2' => [
            'id' => 8,
            'title' => 'Second image',
            'file_name' => 'b.png',
        ],
    ]))->toBe('First image (#5), Second image (#8)')
        ->and(ActivityEntryPresenter::formatValue([
            ['id' => 1, 'title' => 'A'],
            ['id' => 2, 'title' => 'B'],
            ['id' => 3, 'title' => 'C'],
            ['id' => 4, 'title' => 'D'],
        ]))->toBe('A (#1), B (#2), C (#3) +1')
        ->and(ActivityEntryPresenter::formatValue([5, 8]))->toBe('#5, #8');
});

it('returns an empty array for missing or identical changes', function (): void {
    expect(ActivityEntryPresenter::flattenChanges(null))->toBe([])
        ->and(ActivityEntryPresenter::flattenChanges([
            'old' => ['status' => 'draft'],
            'attributes' => ['status' => 'draft'],
        ]))->toBe([]);
});

it('builds a readable subject label from type and title', function (): void {
    $item = new TestAuditableItem;
    $item->setRawAttributes([
        'id' => 7,
        'title' => 'Hello',
        'status' => 'draft',
    ], true);

    $activity = new Activity;
    $activity->subject_type = TestAuditableItem::class;
    $activity->subject_id = 7;
    $activity->setRelation('subject', $item);

    expect(ActivityEntryPresenter::subjectLabel($activity))
        ->toBe('Test Auditable Item: Hello')
        ->and(ActivityEntryPresenter::subjectIdLabel($activity))->toBe('#7')
        ->and(ActivityEntryPresenter::subjectIsUnavailable($activity))->toBeFalse();
});

it('marks missing subjects as unavailable and includes the id in the label', function (): void {
    $activity = new Activity;
    $activity->subject_type = TestAuditableItem::class;
    $activity->subject_id = 42;
    $activity->setRelation('subject', null);

    expect(ActivityEntryPresenter::subjectLabel($activity))
        ->toBe('Test Auditable Item #42')
        ->and(ActivityEntryPresenter::subjectIsUnavailable($activity))->toBeTrue()
        ->and(ActivityEntryPresenter::subjectUnavailableHint($activity))
        ->toBe(__('core::audit.subject_unavailable'));
});

it('only shows a tooltip for change values that exceed the display limit', function (): void {
    $short = str_repeat('a', ActivityEntryPresenter::CHANGE_VALUE_DISPLAY_LIMIT);
    $long = $short.'z';

    expect(ActivityEntryPresenter::truncatedChangeTooltip($short))->toBeNull()
        ->and(ActivityEntryPresenter::truncatedChangeTooltip($long))->toBe($long)
        ->and(ActivityEntryPresenter::truncatedChangeTooltip(null))->toBeNull();
});

it('builds a readable causer label from the causer name', function (): void {
    $causer = new TestAuditableItem;
    $causer->setRawAttributes([
        'id' => 3,
        'title' => 'Aziz',
        'status' => 'draft',
    ], true);

    $activity = new Activity;
    $activity->setRelation('causer', $causer);

    expect(ActivityEntryPresenter::causerLabel($activity))->toBe('Aziz');
});

it('builds a compact changed-fields summary for list columns', function (): void {
    $changes = [
        'old' => ['title' => 'A', 'status' => 'draft'],
        'attributes' => [
            'title' => 'B',
            'status' => 'published',
            'color' => 'red',
            'weight' => 10,
        ],
    ];

    expect(ActivityEntryPresenter::changedFieldsSummary($changes))->toBe('Title, Status, Color +1')
        ->and(ActivityEntryPresenter::changedFieldsSummary($changes, 2))->toBe('Title, Status +2')
        ->and(ActivityEntryPresenter::changedFieldsSummary(null))->toBe('—');
});

it('builds structured change rows for the detail view', function (): void {
    $rows = ActivityEntryPresenter::changeRows([
        'old' => ['title' => 'Hallo', 'status' => 'draft'],
        'attributes' => ['title' => 'Hallo und ciao', 'color' => 'red'],
    ]);

    expect($rows)->toBe([
        [
            'field' => 'Title',
            'old' => 'Hallo',
            'new' => 'Hallo und ciao',
            'kind' => 'changed',
        ],
        [
            'field' => 'Status',
            'old' => 'draft',
            'new' => null,
            'kind' => 'removed',
        ],
        [
            'field' => 'Color',
            'old' => null,
            'new' => 'red',
            'kind' => 'added',
        ],
    ]);
});

it('builds a headline and hides duplicate descriptions', function (): void {
    $item = new TestAuditableItem;
    $item->setRawAttributes([
        'id' => 1,
        'title' => 'Hallo',
        'status' => 'draft',
    ], true);

    $causer = new TestAuditableItem;
    $causer->setRawAttributes([
        'id' => 2,
        'title' => 'Aziz',
        'status' => 'draft',
    ], true);

    $activity = new Activity;
    $activity->event = 'updated';
    $activity->description = 'updated';
    $activity->subject_type = TestAuditableItem::class;
    $activity->subject_id = 1;
    $activity->setRelation('subject', $item);
    $activity->setRelation('causer', $causer);

    expect(ActivityEntryPresenter::headline($activity))
        ->toBe('Aziz updated Test Auditable Item: Hallo')
        ->and(ActivityEntryPresenter::hasDistinctDescription($activity))->toBeFalse();
});
