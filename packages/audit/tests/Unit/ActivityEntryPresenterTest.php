<?php

declare(strict_types=1);

use Moox\Audit\Models\Activity;
use Moox\Audit\Support\ActivityEntryPresenter;
use Moox\Audit\Tests\Support\TestAttributeLabelResolver;
use Moox\Audit\Tests\Support\TestAuditableItem;
use Moox\Audit\Tests\Support\TestSubjectLabelResolver;
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
        ->and($result['flags'])->toBe('a, b')
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

it('formats link, list and relation-like values for audit display', function (): void {
    expect(ActivityEntryPresenter::formatValue([
        'url' => 'https://moox.org',
        'label' => 'Moox',
        'opens_in_new_tab' => true,
    ]))->toBe('Moox (https://moox.org) ↗')
        ->and(ActivityEntryPresenter::formatValue([
            'url' => 'https://example.com',
            'label' => null,
            'opens_in_new_tab' => false,
        ]))->toBe('https://example.com')
        ->and(ActivityEntryPresenter::formatValue(['red', 'green', 'blue']))->toBe('red, green, blue')
        ->and(ActivityEntryPresenter::formatValue([
            ['id' => 1, 'title' => 'Category A'],
            ['id' => 2, 'name' => 'Category B'],
        ]))->toBe('Category A (#1), Category B (#2)')
        ->and(ActivityEntryPresenter::formatValue([]))->toBe('—');
});

it('formats nested group, repeater and flexible content values for audit display', function (): void {
    expect(ActivityEntryPresenter::formatValue([
        'headline' => 'Hero',
        'enabled' => true,
    ]))->toBe('Headline: Hero; Enabled: true')
        ->and(ActivityEntryPresenter::formatValue([
            [
                'title' => 'First',
                'active' => true,
            ],
            [
                'title' => 'Second',
                'active' => false,
            ],
            [
                'title' => 'Third',
            ],
        ]))->toBe('Title: First; Active: true | Title: Second; Active: false +1')
        ->and(ActivityEntryPresenter::formatValue([
            [
                'type' => 'hero_block',
                'data' => [
                    'headline' => 'Welcome',
                    'cta_label' => 'Buy now',
                ],
            ],
            [
                'type' => 'faq',
                'data' => [
                    'question' => 'Why?',
                ],
            ],
            [
                'type' => 'gallery',
                'data' => [
                    'images' => [
                        ['id' => 1, 'title' => 'A'],
                    ],
                ],
            ],
        ]))->toBe('Hero Block: Headline: Welcome; Cta Label: Buy now | Faq: Question: Why? +1');
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

    expect(ActivityEntryPresenter::changedFieldsSummary($changes))->toBe('Title → B, Status → published, Color: red +1')
        ->and(ActivityEntryPresenter::changedFieldsSummary($changes, 2))->toBe('Title → B, Status → published +2')
        ->and(ActivityEntryPresenter::changedFieldsSummary(null))->toBe('—');
});

it('shows gateway failure values in the changed-fields summary', function (): void {
    $changes = [
        'old' => ['gateway_status' => 'generating'],
        'attributes' => ['gateway_status' => 'generation_failed'],
    ];

    expect(ActivityEntryPresenter::changedFieldsSummary($changes))->toBe('Gateway Status → Generation Failed (generation_failed)');
});

it('uses attribute_label_resolver for field and value labels', function (): void {
    $this->registerTestAuditableModel([
        'attribute_label_resolver' => TestAttributeLabelResolver::class,
    ]);

    $activity = new Activity;
    $activity->subject_type = TestAuditableItem::class;
    $activity->attribute_changes = [
        'old' => ['review_status' => 'db_validated'],
        'attributes' => ['review_status' => 'human_confirmed'],
    ];

    $rows = ActivityEntryPresenter::changeRows($activity->attribute_changes, $activity);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['field'])->toBe('Review')
        ->and($rows[0]['old'])->toBe('Automatically pre-reviewed (db_validated)')
        ->and($rows[0]['new'])->toBe('Manually confirmed (human_confirmed)')
        ->and($rows[0]['kind'])->toBe('changed')
        ->and(ActivityEntryPresenter::changedFieldsSummary($activity->attribute_changes, activity: $activity))
        ->toBe('Review → Manually confirmed (human_confirmed)');
});

it('uses configured field_labels and value_labels maps', function (): void {
    $this->registerTestAuditableModel([
        'field_labels' => [
            'review_status' => 'Prüfstatus',
        ],
        'value_labels' => [
            'review_status' => [
                'db_validated' => 'Automatisch vorgeprüft',
                'human_confirmed' => 'Manuell bestätigt',
            ],
        ],
    ]);

    $activity = new Activity;
    $activity->subject_type = TestAuditableItem::class;

    $rows = ActivityEntryPresenter::changeRows([
        'old' => ['review_status' => 'db_validated'],
        'attributes' => ['review_status' => 'human_confirmed'],
    ], $activity);

    expect($rows[0]['field'])->toBe('Prüfstatus')
        ->and($rows[0]['old'])->toBe('Automatisch vorgeprüft (db_validated)')
        ->and($rows[0]['new'])->toBe('Manuell bestätigt (human_confirmed)');
});

it('masks sensitive values without exposing raw secrets', function (): void {
    config()->set('audit.mask_attributes', ['password']);

    $rows = ActivityEntryPresenter::changeRows([
        'old' => ['password' => 'old-secret'],
        'attributes' => ['password' => 'new-secret'],
    ]);

    expect($rows[0]['old'])->toBe(ActivityEntryPresenter::SENSITIVE_VALUE_MASK)
        ->and($rows[0]['new'])->toBe(ActivityEntryPresenter::SENSITIVE_VALUE_MASK);
});

it('detects failure outcomes for list highlighting', function (): void {
    $failedChanges = [
        'old' => ['gateway_status' => 'generating'],
        'attributes' => ['gateway_status' => 'generation_failed'],
    ];

    $successChanges = [
        'old' => ['gateway_status' => 'validating'],
        'attributes' => ['gateway_status' => 'validated'],
    ];

    expect(ActivityEntryPresenter::isFailureEntry($failedChanges))->toBeTrue()
        ->and(ActivityEntryPresenter::isFailureEntry($successChanges))->toBeFalse()
        ->and(ActivityEntryPresenter::failureOutcomeValue($failedChanges))->toBe('generation_failed')
        ->and(ActivityEntryPresenter::listRecordClasses($failedChanges))->toContain('border-danger-600')
        ->and(ActivityEntryPresenter::listRecordClasses($successChanges))->toBeNull();
});

it('builds structured change rows for the detail view', function (): void {
    $rows = ActivityEntryPresenter::changeRows([
        'old' => ['title' => 'Hallo', 'status' => 'draft'],
        'attributes' => ['title' => 'Hallo und ciao', 'color' => 'red'],
    ]);

    expect($rows)->toHaveCount(3)
        ->and($rows[0])->toMatchArray([
            'field' => 'Title',
            'old' => 'Hallo',
            'new' => 'Hallo und ciao',
            'kind' => 'changed',
        ])
        ->and($rows[1])->toMatchArray([
            'field' => 'Status',
            'old' => 'draft',
            'new' => null,
            'kind' => 'removed',
        ])
        ->and($rows[2])->toMatchArray([
            'field' => 'Color',
            'old' => null,
            'new' => 'red',
            'kind' => 'added',
        ]);
});

it('masks sensitive values in structured change rows', function (): void {
    config()->set('audit.mask_attributes', ['password']);

    $rows = ActivityEntryPresenter::changeRows([
        'old' => ['password' => 'old-secret'],
        'attributes' => ['password' => 'new-secret'],
    ]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0])->toMatchArray([
            'field' => 'Password',
            'old' => ActivityEntryPresenter::SENSITIVE_VALUE_MASK,
            'new' => ActivityEntryPresenter::SENSITIVE_VALUE_MASK,
            'kind' => 'changed',
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
        ->toBe('Aziz Updated Test Auditable Item: Hallo')
        ->and(ActivityEntryPresenter::hasDistinctDescription($activity))->toBeFalse();
});

it('uses configured label and title_attribute for subject labels', function (): void {
    $this->registerTestAuditableModel([
        'label' => 'Article',
        'title_attribute' => 'title',
    ]);

    $item = new TestAuditableItem;
    $item->setRawAttributes([
        'id' => 11,
        'title' => 'Readable title',
        'status' => 'draft',
    ], true);

    $activity = new Activity;
    $activity->subject_type = TestAuditableItem::class;
    $activity->subject_id = 11;
    $activity->setRelation('subject', $item);

    expect(ActivityEntryPresenter::subjectTypeLabel(TestAuditableItem::class))->toBe('Article')
        ->and(ActivityEntryPresenter::subjectLabel($activity))->toBe('Article: Readable title');
});

it('falls back to snapshot attributes when the subject is missing', function (): void {
    $this->registerTestAuditableModel([
        'label' => 'Article',
        'title_attribute' => 'title',
    ]);

    $activity = new Activity;
    $activity->subject_type = TestAuditableItem::class;
    $activity->subject_id = 99;
    $activity->attribute_changes = [
        'old' => [
            'title' => 'Deleted article',
            'status' => 'draft',
        ],
    ];
    $activity->setRelation('subject', null);

    expect(ActivityEntryPresenter::subjectLabel($activity))->toBe('Article: Deleted article')
        ->and(ActivityEntryPresenter::subjectAttributeValue($activity, 'title'))->toBe('Deleted article');
});

it('uses a subject_label_resolver when configured', function (): void {
    $this->registerTestAuditableModel([
        'subject_label_resolver' => TestSubjectLabelResolver::class,
    ]);

    $activity = new Activity;
    $activity->subject_type = TestAuditableItem::class;
    $activity->subject_id = 5;
    $activity->setRelation('subject', null);

    expect(ActivityEntryPresenter::subjectLabel($activity))->toBe('Custom subject label');
});
