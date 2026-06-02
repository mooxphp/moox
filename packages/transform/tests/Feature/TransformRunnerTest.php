<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\TransformRunner;
use Moox\Transform\Support\TransformValidator;
use Tests\TestCase;

/**
 * @property string|null $title
 * @property int|null $stock
 * @property string|null $price_label
 */
final class TransformDummyModel extends Model
{
    protected $table = 'transform_dummy_models';

    protected $fillable = [
        'title',
        'stock',
        'price_label',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
        ];
    }
}

/**
 * @property int|null $id
 * @property string|null $status
 */
final class TransformDraftMainModel extends BaseDraftModel
{
    protected $table = 'transform_draft_main_models';

    public $incrementing = true;

    protected $keyType = 'int';

    public string $translationModel = TransformDraftMainTranslationModel::class;

    public string $translationForeignKey = 'transform_draft_main_model_id';

    public string $localeKey = 'locale';

    public bool $useTranslationFallback = true;

    protected $fillable = [
        'status',
    ];

    /**
     * @return array<int, string>
     */
    protected function getCustomTranslatedAttributes(): array
    {
        return [
            'title',
        ];
    }
}

/**
 * @property string|null $title
 */
final class TransformDraftMainTranslationModel extends BaseDraftTranslationModel
{
    protected $table = 'transform_draft_main_model_translations';

    /**
     * @return array<int, string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'transform_draft_main_model_id',
            'title',
        ];
    }
}

uses(TestCase::class);

test('it transforms from multiple source rows and tables into one destination model', function (): void {
    createTestTables();
    Schema::create('legacy_products', function (Blueprint $table): void {
        $table->id();
        $table->string('sku')->unique();
        $table->string('title');
        $table->integer('inventory');
    });
    Schema::create('legacy_prices', function (Blueprint $table): void {
        $table->id();
        $table->string('sku')->unique();
        $table->string('label');
    });
    DB::table('legacy_products')->insert([
        'sku' => 'P-15',
        'title' => 'Switch',
        'inventory' => 19,
    ]);
    DB::table('legacy_prices')->insert([
        'sku' => 'P-15',
        'label' => '9.99 EUR',
    ]);
    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'source_references' => [
            [
                'source_type' => 'db_table',
                'table' => 'legacy_products',
                'row_key' => 'P-15',
                'key_column' => 'sku',
                'alias' => 'product',
            ],
            [
                'source_type' => 'db_table',
                'table' => 'legacy_prices',
                'row_key' => 'P-15',
                'key_column' => 'sku',
                'alias' => 'price',
            ],
        ],
        'field_map' => [
            'title' => 'product.title',
            'stock' => 'product.inventory',
            'price_label' => 'price.label',
        ],
        'validation_rules' => [
            'title' => ['required', 'string'],
            'stock' => ['required', 'integer'],
            'price_label' => ['required', 'string'],
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
    ]);

    (new TransformRunner(new TransformValidator))->run($record);

    $record->refresh();
    expect($record->status)->toBe('processed');
    expect($record->validation_status)->toBe('valid');
    expect($record->degraded)->toBeFalse();

    $saved = TransformDummyModel::query()->first();
    expect($saved)->not()->toBeNull();
    expect($saved?->title)->toBe('Switch');
    expect($saved?->stock)->toBe(19);
    expect($saved?->price_label)->toBe('9.99 EUR');
});

test('it fails validation and stores validation errors', function (): void {
    createTestTables();
    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'field_map' => [
            'title' => 'legacy.title',
            'stock' => 'legacy.inventory',
        ],
        'validation_rules' => [
            'title' => ['required', 'string'],
            'stock' => ['required', 'integer'],
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'inventory' => 5,
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('failed_validation');
    expect($record->validation_status)->toBe('invalid');
    expect($record->validation_errors)->toHaveKey('title');
    expect($record->error_message)->toBe('Validation failed.');
});

test('it rejects definition when destination model does not exist', function (): void {
    createTestTables();
    createDefinition([
        'destination_model' => 'App\\Models\\DefinitelyMissingModel',
        'field_map' => [
            'title' => 'legacy.title',
        ],
    ]);
})->throws(ValidationException::class);

test('it validates based on model metadata when no extra rules are provided', function (): void {
    createTestTables();
    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'field_map' => [
            'title' => 'legacy.title',
            'stock' => 'legacy.inventory',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'title' => 'Adapter',
                'inventory' => 'not-an-integer',
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('failed_validation');
    expect($record->validation_status)->toBe('invalid');
    expect($record->validation_errors)->toHaveKey('stock');
});

test('it allows adding extra validation rules on top of model-based validation', function (): void {
    createTestTables();
    $definition = createDefinition([
        'destination_model' => TransformDummyModel::class,
        'field_map' => [
            'title' => 'legacy.title',
            'stock' => 'legacy.inventory',
        ],
        'validation_rules' => [
            'title' => ['required', 'string', 'min:3'],
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'title' => 'A',
                'inventory' => 12,
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('failed_validation');
    expect($record->validation_status)->toBe('invalid');
    expect($record->validation_errors)->toHaveKey('title');
});

test('it rejects definition with non existing file reference', function (): void {
    createTestTables();

    TransformDefinition::query()->create([
        'name' => 'Invalid file definition',
        'destination_model' => TransformDummyModel::class,
        'source_references' => [
            [
                'source_type' => 'file_json',
                'path' => '/definitely/not/here.json',
                'alias' => 'file',
            ],
        ],
        'field_map' => [
            'title' => 'file.title',
        ],
        'validation_rules' => [],
        'is_active' => true,
    ]);
})->throws(ValidationException::class);

test('it rejects record when no source is provided by record or definition', function (): void {
    createTestTables();
    $definition = createDefinition([
        'source_references' => [],
    ]);

    TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
    ]);
})->throws(ValidationException::class);

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

function createTestTables(): void
{
    Schema::dropIfExists('transform_dummy_models');
    Schema::dropIfExists('transform_draft_main_model_translations');
    Schema::dropIfExists('transform_draft_main_models');
    Schema::dropIfExists('transform_records');
    Schema::dropIfExists('transform_definitions');

    Schema::create('transform_definitions', function (Blueprint $table): void {
        $table->id();
        $table->timestamps();
        $table->softDeletes();
        $table->string('name')->unique();
        $table->string('destination_model');
        $table->json('source_references');
        $table->json('field_map');
        $table->json('validation_rules')->nullable();
        $table->boolean('is_active')->default(true);
    });

    Schema::create('transform_records', function (Blueprint $table): void {
        $table->id();
        $table->timestamps();
        $table->softDeletes();
        $table->foreignId('transform_definition_id')->constrained('transform_definitions');
        $table->string('destination_key')->nullable();
        $table->json('source_projection')->nullable();
        $table->json('source_references')->nullable();
        $table->string('input_hash', 64)->nullable();
        $table->string('status')->default('pending');
        $table->string('validation_status')->default('pending');
        $table->json('validation_errors')->nullable();
        $table->json('warnings')->nullable();
        $table->unsignedInteger('attempts')->default(0);
        $table->boolean('degraded')->default(false);
        $table->timestamp('last_run_at')->nullable();
        $table->timestamp('last_success_at')->nullable();
        $table->text('error_message')->nullable();
    });

    Schema::create('transform_dummy_models', function (Blueprint $table): void {
        $table->id();
        $table->string('title')->nullable();
        $table->integer('stock')->nullable();
        $table->string('price_label')->nullable();
        $table->timestamps();
    });

    Schema::create('transform_draft_main_models', function (Blueprint $table): void {
        $table->id();
        $table->uuid('uuid')->nullable();
        $table->ulid('ulid')->nullable();
        $table->string('status')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('transform_draft_main_model_translations', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('transform_draft_main_model_id')->constrained('transform_draft_main_models')->cascadeOnDelete();
        $table->string('locale');
        $table->string('title')->nullable();
        $table->string('translation_status')->nullable();
        $table->timestamp('to_publish_at')->nullable();
        $table->timestamp('published_at')->nullable();
        $table->timestamp('to_unpublish_at')->nullable();
        $table->timestamp('unpublished_at')->nullable();
        $table->unsignedBigInteger('published_by_id')->nullable();
        $table->string('published_by_type')->nullable();
        $table->unsignedBigInteger('unpublished_by_id')->nullable();
        $table->string('unpublished_by_type')->nullable();
        $table->unsignedBigInteger('deleted_by_id')->nullable();
        $table->string('deleted_by_type')->nullable();
        $table->timestamp('restored_at')->nullable();
        $table->unsignedBigInteger('restored_by_id')->nullable();
        $table->string('restored_by_type')->nullable();
        $table->unsignedBigInteger('created_by_id')->nullable();
        $table->string('created_by_type')->nullable();
        $table->unsignedBigInteger('updated_by_id')->nullable();
        $table->string('updated_by_type')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });
}

function makeRunner(): TransformRunner
{
    return new TransformRunner(new TransformValidator);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function createDefinition(array $overrides = []): TransformDefinition
{
    return TransformDefinition::query()->create(array_replace_recursive([
        'name' => 'Test Definition',
        'destination_model' => TransformDummyModel::class,
        'source_references' => [],
        'field_map' => [
            'title' => 'legacy.title',
        ],
        'validation_rules' => [],
        'is_active' => true,
    ], $overrides));
}
