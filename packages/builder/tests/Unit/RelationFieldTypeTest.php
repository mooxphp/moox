<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';
require_once __DIR__.'/../Support/TestLocalizationLikeResource.php';
require_once __DIR__.'/../Support/TestCategoryLike.php';
require_once __DIR__.'/../Support/TestCategoryLikeTranslation.php';
require_once __DIR__.'/../Support/TestCategoryLikeResource.php';

use Filament\Forms\Components\Select;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\RelationSettings;
use Moox\Builder\FieldTypes\Types\RelationFieldType;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Services\BuilderValuesResolver;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Support\RelationTargetResolver;
use Moox\Builder\Support\RelationValueRules;
use Moox\Builder\Tests\Support\TestCategoryLike;
use Moox\Builder\Tests\Support\TestCategoryLikeResource;
use Moox\Builder\Tests\Support\TestCategoryLikeTranslation;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\Support\TestLocalizationLikeResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();
    Cache::forget(DefinitionRegistry::CACHE_KEY);
    bindRelationTestEntityRegistry();
});

function bindRelationTestEntityRegistry(): void
{
    $registry = new class extends EntityRegistry
    {
        protected function panelResources(): array
        {
            return [TestItemResource::class];
        }
    };

    app()->instance(EntityRegistry::class, $registry);
}

function relationFieldDefinition(bool $multiple = false): FieldDefinition
{
    return new FieldDefinition(
        name: 'linked-item',
        label: 'Linked item',
        type: 'relation',
        config: [
            'related_entity' => 'item',
            'multiple' => $multiple,
        ],
    );
}

it('exposes relation settings for the relation field type', function (): void {
    $fields = app(RelationSettings::class)->builderFieldsFor('relation');

    $relatedEntity = collect($fields)->first(fn ($field) => $field->getName() === 'config.related_entity');

    expect($fields)->not->toBeEmpty()
        ->and($relatedEntity)->not->toBeNull()
        ->and($relatedEntity)->toBeInstanceOf(Select::class)
        ->and($relatedEntity->isSearchable())->toBeTrue()
        // Searchable inside the reactive settings schema only persists when it
        // commits immediately via live(); otherwise a rebuild drops the value.
        ->and($relatedEntity->isLive())->toBeTrue();
});

it('builds a searchable select that respects the multiple setting', function (): void {
    $single = (new RelationFieldType)->formComponent(relationFieldDefinition());
    $multiple = (new RelationFieldType)->formComponent(relationFieldDefinition(multiple: true));

    expect($single)->toBeInstanceOf(Select::class)
        ->and($single->isMultiple())->toBeFalse()
        ->and($single->isSearchable())->toBeTrue()
        ->and($multiple->isMultiple())->toBeTrue();
});

it('preloads initial relation suggestions without searching', function (): void {
    $first = TestItem::query()->create(['title' => 'First option']);
    $second = TestItem::query()->create(['title' => 'Second option']);

    $component = (new RelationFieldType)->formComponent(relationFieldDefinition());

    expect($component->isPreloaded())->toBeTrue()
        ->and($component->getSearchResults(''))->toBe([
            $first->getKey() => 'First option',
            $second->getKey() => 'Second option',
        ]);
});

it('wraps relation validation rules for Filament form evaluation', function (): void {
    $rules = RelationValueRules::rules(relationFieldDefinition());

    expect($rules)->toHaveCount(1)
        ->and($rules[0])->toBeInstanceOf(Closure::class)
        ->and($rules[0]())->toBeInstanceOf(Closure::class);
});

it('casts and persists relation ids for single and multiple fields', function (): void {
    $type = new RelationFieldType;
    $singleField = relationFieldDefinition();
    $multipleField = relationFieldDefinition(multiple: true);

    expect($type->castValue(null, $singleField))->toBeNull()
        ->and($type->castValue(3, $singleField))->toBe(3)
        ->and($type->castValue(null, $multipleField))->toBe([])
        ->and($type->castValue([1, 2], $multipleField))->toBe([1, 2])
        ->and($type->persistValue(4, $singleField))->toBe(4)
        ->and($type->persistValue([5, 6], $multipleField))->toBe([5, 6]);
});

it('round trips single relation ids through field group persistence', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Relations',
        'slug' => 'relations',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Relations',
        'slug' => 'relations',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'linked-item',
                'label' => 'Linked item',
                'type' => 'relation',
                'required' => false,
                'config' => [
                    'related_entity' => 'item',
                    'multiple' => false,
                ],
            ],
        ],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $target = TestItem::query()->create(['title' => 'Related record']);
    $record = TestItem::query()->create(['title' => 'Owner']);
    $manager = app(CustomFieldsManager::class);
    $fields = $manager->fieldsForEntity('item');

    $manager->saveValues('item', $record, ['linked-item' => $target->getKey()], $fields);

    expect($manager->loadValues('item', $record, $fields))->toBe([
        'linked-item' => $target->getKey(),
    ]);
});

it('round trips multiple relation ids through field group persistence', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Relations multiple',
        'slug' => 'relations-multiple',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    app(FieldGroupPersistence::class)->sync($group, [
        'name' => 'Relations multiple',
        'slug' => 'relations-multiple',
        'active' => true,
        'sort' => 0,
        'target_entities' => ['item'],
        'fields' => [
            [
                'name' => 'linked-items',
                'label' => 'Linked items',
                'type' => 'relation',
                'required' => false,
                'config' => [
                    'related_entity' => 'item',
                    'multiple' => true,
                    'min' => 1,
                    'max' => 3,
                ],
            ],
        ],
    ]);

    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $first = TestItem::query()->create(['title' => 'First']);
    $second = TestItem::query()->create(['title' => 'Second']);
    $record = TestItem::query()->create(['title' => 'Owner']);
    $manager = app(CustomFieldsManager::class);
    $fields = $manager->fieldsForEntity('item');

    $manager->saveValues('item', $record, [
        'linked-items' => [$first->getKey(), $second->getKey()],
    ], $fields);

    expect($manager->loadValues('item', $record, $fields))->toBe([
        'linked-items' => [$first->getKey(), $second->getKey()],
    ]);
});

it('presents relation values as resolved id and label objects', function (): void {
    $target = TestItem::query()->create(['title' => 'Resolved title']);
    $field = relationFieldDefinition();
    $type = new RelationFieldType;

    expect($type->presentValue($target->getKey(), $field))->toBe([
        'id' => $target->getKey(),
        'label' => 'Resolved title',
    ]);
});

it('filters deleted relation targets from presented values', function (): void {
    $target = TestItem::query()->create(['title' => 'Gone soon']);
    $deletedId = $target->getKey();
    $target->delete();

    $field = relationFieldDefinition();
    $type = new RelationFieldType;

    expect($type->presentValue($deletedId, $field))->toBeNull()
        ->and($type->presentValue([$deletedId], relationFieldDefinition(multiple: true)))->toBe([]);
});

it('resolves searchable labels and batch output through the target resolver', function (): void {
    $first = TestItem::query()->create(['title' => 'Alpha']);
    $second = TestItem::query()->create(['title' => 'Beta']);
    $resolver = app(RelationTargetResolver::class);

    expect($resolver->search('item', 'Al'))->toHaveKey($first->getKey())
        ->and($resolver->labelsFor('item', [$first->getKey(), $second->getKey()]))->toBe([
            $first->getKey() => 'Alpha',
            $second->getKey() => 'Beta',
        ])
        ->and($resolver->resolve('item', [$second->getKey(), $first->getKey()]))->toBe([
            ['id' => $second->getKey(), 'label' => 'Beta'],
            ['id' => $first->getKey(), 'label' => 'Alpha'],
        ]);
});

it('memoizes relation labels within a request to avoid repeated queries', function (): void {
    $item = TestItem::query()->create(['title' => 'Cached']);
    $resolver = app(RelationTargetResolver::class);

    DB::enableQueryLog();

    $resolver->labelsFor('item', [$item->getKey()]);
    $resolver->labelsFor('item', [$item->getKey()]);

    $itemQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], '"items"') || str_contains($query['query'], '`items`'));

    DB::disableQueryLog();

    expect($itemQueries)->toHaveCount(1);
});

it('uses record titles when the resource has no record title attribute', function (): void {
    $first = TestItem::query()->create(['title' => 'English']);
    $second = TestItem::query()->create(['title' => 'German']);

    $registry = new class extends EntityRegistry
    {
        protected function panelResources(): array
        {
            return [TestLocalizationLikeResource::class];
        }
    };

    app()->instance(EntityRegistry::class, $registry);

    $resolver = app(RelationTargetResolver::class);

    expect($resolver->search('item', ''))->toBe([
        $first->getKey() => 'English',
        $second->getKey() => 'German',
    ]);
});

it('presents relation values through the values resolver', function (): void {
    $target = TestItem::query()->create(['title' => 'Via resolver']);
    $field = relationFieldDefinition();

    $presented = app(BuilderValuesResolver::class)->present(
        collect([$field]),
        ['linked-item' => $target->getKey()],
    );

    expect($presented)->toBe([
        'linked-item' => [
            'id' => $target->getKey(),
            'label' => 'Via resolver',
        ],
    ]);
});

it('rejects relation values that point to missing records', function (): void {
    $field = relationFieldDefinition();

    expect(fn () => RelationValueRules::assertValid($field, 999))->toThrow(ValidationException::class);
});

it('returns an empty search result when the target table is missing', function (): void {
    Schema::dropIfExists('items');

    expect(app(RelationTargetResolver::class)->search('item', 'ad'))->toBe([]);
});

it('resolves display titles for translation-backed relation targets', function (): void {
    Schema::dropIfExists('category_translations');
    Schema::dropIfExists('categories');

    Schema::create('categories', function (Blueprint $table): void {
        $table->id();
    });

    Schema::create('category_translations', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('category_id');
        $table->string('locale');
        $table->string('title')->nullable();
    });

    $registry = new class extends EntityRegistry
    {
        protected function panelResources(): array
        {
            return [TestCategoryLikeResource::class];
        }
    };

    app()->instance(EntityRegistry::class, $registry);

    $category = TestCategoryLike::query()->create();
    TestCategoryLikeTranslation::query()->create([
        'category_id' => $category->getKey(),
        'locale' => 'en_US',
        'title' => 'News',
    ]);

    $resolver = app(RelationTargetResolver::class);

    expect($resolver->search('test_category_like', ''))->toBe([
        $category->getKey() => 'News',
    ])->and($resolver->labelsFor('test_category_like', [$category->getKey()]))->toBe([
        $category->getKey() => 'News',
    ])->and($resolver->search('test_category_like', 'New'))->toHaveKey($category->getKey());

    $queryTarget = $resolver->queryTarget('test_category_like');

    expect($queryTarget)->not->toBeNull()
        ->and($queryTarget['titleColumn'])->toBe('title')
        ->and($queryTarget['translation']['table'] ?? null)->toBe('category_translations');
});
