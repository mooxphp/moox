<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Http\Resources\Concerns\MergesCustomFields;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\BuilderValuesResolver;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();

    FieldGroup::query()->delete();

    Cache::forget(DefinitionRegistry::CACHE_KEY);
    TestItem::flushCustomFieldDefinitionCache();
});

it('presents date and datetime values as iso strings for api output', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Scheduling',
        'slug' => 'scheduling',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'starts_on',
        'label' => 'Starts on',
        'type' => 'date',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'starts_at',
        'label' => 'Starts at',
        'type' => 'datetime',
        'sort' => 1,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'opens_at',
        'label' => 'Opens at',
        'type' => 'time',
        'sort' => 2,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $record = TestItem::query()->create(['title' => 'Demo']);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $record->getKey(),
        'field_name' => 'starts_on',
        'value_date' => '2026-06-16',
    ]);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $record->getKey(),
        'field_name' => 'starts_at',
        'value_datetime' => '2026-06-16 14:30:00',
    ]);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $record->getKey(),
        'field_name' => 'opens_at',
        'value_string' => '14:30',
    ]);

    $record->flushCustomFieldsCache();

    $resource = new class($record) extends JsonResource
    {
        use MergesCustomFields;

        public function toArray($request): array
        {
            return $this->mergeCustomFields([]);
        }
    };

    $api = $resource->resolve();

    expect($api['starts_on'])->toBe('2026-06-16')
        ->and($api['starts_at'])->toBe(Carbon::parse('2026-06-16 14:30:00')->toIso8601String())
        ->and($api['opens_at'])->toBe('14:30');

    $raw = $record->customFields(fresh: true);

    expect($raw['starts_on'])->toBeInstanceOf(Carbon::class)
        ->and($raw['starts_at'])->toBeInstanceOf(Carbon::class);
});

it('masks password values for api output while keeping plaintext in internal arrays', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Secrets',
        'slug' => 'secrets',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'secret',
        'label' => 'Secret',
        'type' => 'password',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $record = TestItem::query()->create(['title' => 'Demo']);

    FieldValue::query()->create([
        'entity' => 'item',
        'record_id' => $record->getKey(),
        'field_name' => 'secret',
        'value_string' => Hash::make('super-secret'),
    ]);

    $record->flushCustomFieldsCache();

    $resource = new class($record) extends JsonResource
    {
        use MergesCustomFields;

        public function toArray($request): array
        {
            return $this->mergeCustomFields([]);
        }
    };

    expect($resource->resolve()['secret'])->toBe(['has_value' => true])
        ->and($record->toArray()['secret'])->toBeNull();
});

it('presents nested group values recursively for api output', function (): void {
    $field = new FieldDefinition(
        name: 'contact',
        label: 'Contact',
        type: 'group',
        children: collect([
            new FieldDefinition(name: 'birthday', label: 'Birthday', type: 'date'),
            new FieldDefinition(name: 'pin', label: 'PIN', type: 'password'),
        ]),
    );

    $presented = app(BuilderValuesResolver::class)->present(
        collect([$field]),
        [
            'contact' => [
                'birthday' => '1990-05-01',
                'pin' => '1234',
            ],
        ],
    );

    expect($presented['contact'])->toMatchArray([
        'birthday' => '1990-05-01',
        'pin' => ['has_value' => true],
    ]);
});

it('merges presented custom fields into api resources', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Basics',
        'slug' => 'basics',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'farbe',
        'label' => 'Farbe',
        'type' => 'text',
        'sort' => 0,
        'validation' => ['required' => false, 'rules' => []],
    ]);

    $record = TestItem::query()->create(['title' => 'Demo']);
    $record->setCustomField('farbe', 'Blau');

    $resource = new class($record) extends JsonResource
    {
        use MergesCustomFields;

        public function toArray($request): array
        {
            return $this->mergeCustomFields([
                'title' => $this->title,
            ]);
        }
    };

    expect($resource->resolve())->toMatchArray([
        'title' => 'Demo',
        'farbe' => 'Blau',
    ])
        ->and($record->customFields()['farbe'])->toBe('Blau');
});
