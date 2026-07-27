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
        ->and(ActivityEntryPresenter::subjectIdLabel($activity))->toBe('#7');
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

it('builds structured change rows for the detail view', function (): void {
    $rows = ActivityEntryPresenter::changeRows([
        'old' => ['title' => 'Hallo', 'status' => 'draft'],
        'attributes' => ['title' => 'Hallo und ciao', 'color' => 'red'],
    ]);

    expect($rows)->toBe([
        [
            'field' => 'title',
            'old' => 'Hallo',
            'new' => 'Hallo und ciao',
            'kind' => 'changed',
        ],
        [
            'field' => 'status',
            'old' => 'draft',
            'new' => null,
            'kind' => 'removed',
        ],
        [
            'field' => 'color',
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
