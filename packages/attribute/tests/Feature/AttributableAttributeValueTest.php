<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Moox\Attribute\Models\Attribute;
use Moox\Attribute\Models\AttributeValues;
use Moox\Attribute\Models\AttributableAttributeValue;
use Moox\Attribute\Models\Concerns\HasAttributeValues;

const ATTRIBUTABLE_TEST_TABLE = 'test_attributables';

beforeEach(function (): void {
    Schema::dropIfExists(ATTRIBUTABLE_TEST_TABLE);
    Schema::create(ATTRIBUTABLE_TEST_TABLE, function (Blueprint $table): void {
        $table->id();
    });
});

afterEach(function (): void {
    Schema::dropIfExists(ATTRIBUTABLE_TEST_TABLE);
});

/**
 * One anonymous model class for all tests (stable morph type string).
 *
 * @return class-string<Model>
 */
function attributableAnonymousTestModelClass(): string
{
    static $class = null;

    if ($class === null) {
        $class = get_class(new class extends Model
        {
            use HasAttributeValues;

            protected $table = ATTRIBUTABLE_TEST_TABLE;

            public $timestamps = false;

            protected $guarded = [];
        });
    }

    return $class;
}

function newAttributableTestSubject(): Model
{
    $class = attributableAnonymousTestModelClass();

    return new $class;
}

it('attaches an attribute value to a model and resolves the attribute definition', function (): void {
    $attribute = Attribute::query()->create([
        'type' => 'text',
        'name' => 'Size',
        'description' => 'Size attribute',
        'status' => 'draft',
        'uuid' => (string) Str::uuid(),
        'ulid' => (string) Str::ulid(),
    ]);

    $value = AttributeValues::query()->create([
        'attribute_id' => $attribute->getKey(),
        'value' => ['key' => 'l', 'label' => 'Large'],
    ]);

    $subject = newAttributableTestSubject();
    $subject->save();

    $subject->attributeValues()->attach($value->getKey());

    $this->assertDatabaseHas('attributable_attribute_value', [
        'attributable_type' => attributableAnonymousTestModelClass(),
        'attributable_id' => $subject->getKey(),
        'attribute_value_id' => $value->getKey(),
        'sort_order' => 0,
    ]);

    $subject->load('attributeValues.attribute');

    expect($subject->attributeValues)->toHaveCount(1);
    expect($subject->attributeValues->first()->attribute->is($attribute))->toBeTrue();
    expect($subject->attributeValues->first()->value)->toBe(['key' => 'l', 'label' => 'Large']);
});

it('prevents duplicate assignments for the same model and value', function (): void {
    $attribute = Attribute::query()->create([
        'type' => 'text',
        'name' => 'Color',
        'description' => 'Color attribute',
        'status' => 'draft',
        'uuid' => (string) Str::uuid(),
        'ulid' => (string) Str::ulid(),
    ]);

    $value = AttributeValues::query()->create([
        'attribute_id' => $attribute->getKey(),
        'value' => ['key' => 'red'],
    ]);

    $subject = newAttributableTestSubject();
    $subject->save();
    $subject->attributeValues()->attach($value->getKey());

    expect(fn () => $subject->attributeValues()->attach($value->getKey()))
        ->toThrow(QueryException::class);
});

it('removes pivot rows when the attribute value is deleted', function (): void {
    $attribute = Attribute::query()->create([
        'type' => 'text',
        'name' => 'Weight',
        'description' => 'Weight attribute',
        'status' => 'draft',
        'uuid' => (string) Str::uuid(),
        'ulid' => (string) Str::ulid(),
    ]);

    $value = AttributeValues::query()->create([
        'attribute_id' => $attribute->getKey(),
        'value' => ['kg' => 10],
    ]);

    $subject = newAttributableTestSubject();
    $subject->save();
    $subject->attributeValues()->attach($value->getKey());

    $value->delete();

    $this->assertDatabaseMissing('attributable_attribute_value', [
        'attribute_value_id' => $value->getKey(),
    ]);
});

it('resolves the owning model from the pivot row', function (): void {
    $attribute = Attribute::query()->create([
        'type' => 'text',
        'name' => 'Material',
        'description' => 'Material attribute',
        'status' => 'draft',
        'uuid' => (string) Str::uuid(),
        'ulid' => (string) Str::ulid(),
    ]);

    $value = AttributeValues::query()->create([
        'attribute_id' => $attribute->getKey(),
        'value' => ['key' => 'cotton'],
    ]);

    $subject = newAttributableTestSubject();
    $subject->save();
    $subject->attributeValues()->attach($value->getKey());

    /** @var AttributableAttributeValue $pivot */
    $pivot = AttributableAttributeValue::query()->where('attribute_value_id', $value->getKey())->firstOrFail();

    expect($pivot->attributable->is($subject))->toBeTrue();
    expect($pivot->attributeValue->attribute->is($attribute))->toBeTrue();
});

it('orders attached values by pivot sort_order', function (): void {
    $attribute = Attribute::query()->create([
        'type' => 'text',
        'name' => 'Order',
        'description' => 'Order test',
        'status' => 'draft',
        'uuid' => (string) Str::uuid(),
        'ulid' => (string) Str::ulid(),
    ]);

    $first = AttributeValues::query()->create([
        'attribute_id' => $attribute->getKey(),
        'value' => ['n' => 1],
    ]);

    $second = AttributeValues::query()->create([
        'attribute_id' => $attribute->getKey(),
        'value' => ['n' => 2],
    ]);

    $subject = newAttributableTestSubject();
    $subject->save();

    $subject->attributeValues()->attach([
        $first->getKey() => ['sort_order' => 10],
        $second->getKey() => ['sort_order' => 1],
    ]);

    $ordered = $subject->attributeValues()->get();

    expect($ordered->first()->is($second))->toBeTrue();
    expect($ordered->last()->is($first))->toBeTrue();
});
