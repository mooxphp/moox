<?php

declare(strict_types=1);

use Moox\Audit\Models\Activity;
use Moox\Audit\Observers\ConfigDrivenModelObserver;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\Audit\Support\AuditConfigResolver;
use Moox\Audit\Support\AuditRequestContext;
use Moox\Audit\Support\CustomFieldAuditMerger;
use Moox\Audit\Support\SensitiveAttributeGuard;
use Moox\Audit\Tests\Support\TestAuditableItem;
use Moox\Audit\Tests\Support\TestNonSoftDeleteAuditableItem;
use Moox\Audit\Tests\Support\TestStatusEnum;
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

it('registers a merge target when the observer logs a model update', function (): void {
    /** @var TestCase $this */
    $this->registerTestAuditableModel();

    $item = TestAuditableItem::query()->create([
        'title' => 'Original',
        'status' => 'draft',
    ]);

    $item->update([
        'title' => 'Changed title',
    ]);

    expect(app(AuditRequestContext::class)->mergeTargetId($item, 'updated'))->not->toBeNull();
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

        /**
         * @param  array<string, mixed>  $values
         * @return array<string, mixed>
         */
        public function castFormValues(string $resourceClass, array $values): array
        {
            return $values;
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

        /**
         * @param  array<string, mixed>  $values
         * @return array<string, mixed>
         */
        public function castFormValues(string $resourceClass, array $values): array
        {
            return $values;
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

it('casts raw form custom field values before merging audit changes', function (): void {
    /** @var TestCase $this */
    $this->registerTestAuditableModel();

    $snapshot = [
        'id' => 7,
        'file_name' => 'hero.png',
        'title' => 'Hero image',
        'alt' => 'Screenshot',
    ];

    $item = TestAuditableItem::query()->create([
        'title' => 'Original',
        'status' => 'draft',
        'builder_payload' => [
            'bild' => $snapshot,
            'email' => 'old@example.com',
        ],
    ]);

    $item->update([
        'title' => 'Changed title',
    ]);

    app()->instance('Moox\\Builder\\Services\\CustomFieldsManager', new class($snapshot)
    {
        /**
         * @param  array<string, mixed>  $snapshot
         */
        public function __construct(private array $snapshot)
        {
        }

        /**
         * @return array<string, mixed>
         */
        public function preparedFormValues(string $resourceClass, object $record, array $data): array
        {
            return [
                'bild' => 7,
                'email' => 'new@example.com',
            ];
        }

        /**
         * @param  array<string, mixed>  $values
         * @return array<string, mixed>
         */
        public function castFormValues(string $resourceClass, array $values): array
        {
            return [
                'bild' => $this->snapshot,
                'email' => $values['email'] ?? null,
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
        ->and($activity->attribute_changes?->get('old'))->not->toHaveKey('bild')
        ->and($activity->attribute_changes?->get('attributes'))->not->toHaveKey('bild')
        ->and($activity->attribute_changes?->get('old'))->toMatchArray([
            'email' => 'old@example.com',
        ])
        ->and($activity->attribute_changes?->get('attributes'))->toMatchArray([
            'email' => 'new@example.com',
        ]);
});

it('creates separate audit entries for repeated custom field only saves', function (): void {
    /** @var TestCase $this */
    $this->registerTestAuditableModel();

    $item = TestAuditableItem::query()->create([
        'title' => 'Stable title',
        'status' => 'draft',
        'builder_payload' => [
            'hero_title' => 'First',
        ],
    ]);

    $manager = new class
    {
        public int $call = 0;

        /**
         * @return array<string, mixed>
         */
        public function preparedFormValues(string $resourceClass, object $record, array $data): array
        {
            $this->call++;

            return [
                'hero_title' => match ($this->call) {
                    1 => 'Second',
                    2 => 'Third',
                    default => 'Fourth',
                },
            ];
        }

        /**
         * @param  array<string, mixed>  $values
         * @return array<string, mixed>
         */
        public function castFormValues(string $resourceClass, array $values): array
        {
            return $values;
        }
    };

    app()->instance('Moox\\Builder\\Services\\CustomFieldsManager', $manager);

    foreach (range(1, 3) as $save) {
        app(CustomFieldAuditMerger::class)
            ->mergeUpdated($item, 'TestResource', []);
    }

    $activities = Activity::query()
        ->where('event', 'updated')
        ->where('subject_type', TestAuditableItem::class)
        ->where('subject_id', $item->getKey())
        ->orderBy('id')
        ->get();

    expect($activities)->toHaveCount(3)
        ->and($activities->pluck('attribute_changes')->map(
            fn ($changes) => $changes?->get('attributes')['hero_title'] ?? null,
        )->all())->toBe(['Second', 'Third', 'Fourth']);
});

it('stores masked values for sensitive attributes instead of plaintext', function (): void {
    /** @var TestCase $this */
    $this->registerTestAuditableModel();

    config()->set('audit.mask_attributes', ['password', 'api_key']);

    $item = TestAuditableItem::query()->create([
        'title' => 'User',
        'status' => 'draft',
    ]);

    MooxActivityLogger::audit(
        $item,
        'updated',
        [
            'old' => [
                'password' => 'old-secret',
                'api_key' => 'key-old',
                'title' => 'User',
            ],
            'attributes' => [
                'password' => 'new-secret',
                'api_key' => 'key-new',
                'title' => 'Renamed',
            ],
        ],
        [
            'entry_type' => 'audit',
            'log_name' => 'test',
        ],
        'test',
    );

    $activity = Activity::query()
        ->where('event', 'updated')
        ->where('subject_type', TestAuditableItem::class)
        ->where('subject_id', $item->getKey())
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->attribute_changes?->get('old'))->toMatchArray([
            'password' => SensitiveAttributeGuard::MASK,
            'api_key' => SensitiveAttributeGuard::MASK,
            'title' => 'User',
        ])
        ->and($activity->attribute_changes?->get('attributes'))->toMatchArray([
            'password' => SensitiveAttributeGuard::MASK,
            'api_key' => SensitiveAttributeGuard::MASK,
            'title' => 'Renamed',
        ]);
});

it('masks sensitive custom field values when merging into an activity', function (): void {
    /** @var TestCase $this */
    $this->registerTestAuditableModel();

    config()->set('audit.mask_attributes', ['api_key']);

    $item = TestAuditableItem::query()->create([
        'title' => 'Original',
        'status' => 'draft',
        'builder_payload' => [
            'api_key' => 'old-key',
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
                'api_key' => 'new-key',
                'hero_title' => 'Welcome',
            ];
        }

        /**
         * @param  array<string, mixed>  $values
         * @return array<string, mixed>
         */
        public function castFormValues(string $resourceClass, array $values): array
        {
            return $values;
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
            'api_key' => SensitiveAttributeGuard::MASK,
        ])
        ->and($activity->attribute_changes?->get('attributes'))->toMatchArray([
            'api_key' => SensitiveAttributeGuard::MASK,
            'title' => 'Changed title',
        ])
        ->and($activity->attribute_changes?->get('old')['api_key'] ?? null)->not->toBe('old-key')
        ->and($activity->attribute_changes?->get('attributes')['api_key'] ?? null)->not->toBe('new-key');
});

it('skips non-significant updates when significant_updates is configured', function (): void {
    /** @var TestCase $this */
    $this->registerTestAuditableModel([
        'significant_updates' => [
            'status' => ['published', 'archived'],
        ],
    ]);

    $item = TestAuditableItem::query()->create([
        'title' => 'Original',
        'status' => 'draft',
    ]);

    Activity::query()->delete();

    $item->update(['title' => 'Noise']);

    expect(Activity::query()->where('event', 'updated')->count())->toBe(0);

    $item->update(['status' => 'published', 'title' => 'Also changed']);

    $activity = Activity::query()->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->attribute_changes?->get('attributes'))->toMatchArray([
            'status' => 'published',
        ])
        ->and($activity->attribute_changes?->get('attributes'))->not->toHaveKey('title');
});

it('matches significant_updates when the new value is a backed enum', function (): void {
    /** @var TestCase $this */
    $this->registerTestAuditableModel([
        'significant_updates' => [
            'status' => ['published'],
        ],
    ]);

    $item = TestAuditableItem::query()->create([
        'title' => 'Original',
        'status' => 'draft',
    ]);

    Activity::query()->delete();

    $observer = new ConfigDrivenModelObserver;
    $config = AuditConfigResolver::resolveModel(TestAuditableItem::class);

    expect($config)->not->toBeNull();

    $method = new ReflectionMethod($observer, 'isSignificantUpdateValue');

    expect($method->invoke(
        $observer,
        TestStatusEnum::Published,
        ['published'],
    ))->toBeTrue()
        ->and($method->invoke(
            $observer,
            TestStatusEnum::Draft,
            ['published'],
        ))->toBeFalse();
});
