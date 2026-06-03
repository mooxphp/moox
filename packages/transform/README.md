![Moox Transform](https://github.com/mooxphp/moox/raw/main/art/banner/transform-package.jpg)

# Moox Transform

Moox Transform is a lightweight Laravel package for running structured data transformations into Eloquent models with strong validation and traceability.

The package is built around two clear concepts:

- `TransformDefinition`: what should be transformed
- `TransformRecord`: what happened during one concrete run

## Why This Package

Moox Transform is designed for clean, production-friendly transformation pipelines:

- No large raw payload blobs by default
- Strict source and model validation before processing
- Reusable definitions for repeatable imports
- Auditable run records with status, warnings, and errors

## Core Architecture

### TransformDefinition (configuration blueprint)

Stores reusable transformation configuration:

- `name` (unique)
- `destination_model`
- `source_references`
- `field_map`
- `validation_rules` (optional)
- `is_active`

### TransformRecord (execution log)

Stores one run of a definition:

- `transform_definition_id`
- `destination_key` (optional update target)
- `source_projection` (optional runtime projection)
- `source_references` (optional runtime override)
- `input_hash`
- status and audit fields (`status`, `validation_status`, `validation_errors`, `warnings`, `attempts`, timestamps, `error_message`)

## Supported Source Types

`source_references` supports:

- `db_table`
  - required: `table`, `key_column`
  - optional: `row_key`, `columns`, `alias`
- `file_json`
  - required: `path`
  - optional: `selector`, `alias`
- `file_csv`
  - required: `path`
  - optional: `key_column`, `row_key`, `selector`, `alias`
- `api`
  - required: `url`
  - optional: `query`, `selector`, `alias`

## Validation and Guardrails

Validation is enforced early (model-level), so invalid definitions/records are blocked before queue/runtime logic.

### Definition-level checks

On saving a `TransformDefinition`, the package validates:

- `destination_model` exists and is an Eloquent model class
- `field_map` is a non-empty array
- `source_references` is an array and each reference is valid
  - `db_table`: table and columns really exist
  - `file_json`: file exists, readable, valid JSON
  - `file_csv`: file exists, readable, has header, optional `key_column` exists in header
  - `api`: URL format is valid

### Record-level checks

On saving a `TransformRecord`, the package validates:

- valid `transform_definition_id`
- at least one effective source is present:
  - `source_projection`, or
  - runtime `source_references`, or
  - definition `source_references`
- runtime `source_references` (if provided) are valid

Database constraint:

- `transform_records` enforces source presence with a check constraint (`source_projection IS NOT NULL OR source_references IS NOT NULL`)

## Runtime Flow

`TransformRunner` processes a `TransformRecord` in this order:

1. load and validate active definition
2. resolve sources (`source_projection` + references)
3. build `input_hash`
4. map fields via `field_map`
5. infer model-based validation rules (from fillable/casts)
6. merge definition `validation_rules`
7. validate mapped data
8. write attributes to destination model (with graceful degradation for unmapped attributes)
9. persist run result and audit metadata

## Status Semantics

Common `TransformRecord.status` values:

- `pending`
- `processing`
- `processed`
- `failed_validation`
- `failed`
- `skipped`

`validation_status`:

- `pending`
- `valid`
- `invalid`

## Minimal Example

```php
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\TransformRunner;
use Moox\Transform\Support\TransformValidator;

$definition = TransformDefinition::query()->create([
    'name' => 'products-sync',
    'destination_model' => \App\Models\Product::class,
    'source_references' => [
        [
            'source_type' => 'db_table',
            'table' => 'legacy_products',
            'key_column' => 'sku',
            'row_key' => 'P-15',
            'alias' => 'product',
        ],
    ],
    'field_map' => [
        'name' => 'product.title',
        'stock' => 'product.inventory',
    ],
    'validation_rules' => [
        'name' => ['required', 'string'],
    ],
    'is_active' => true,
]);

$record = TransformRecord::query()->create([
    'transform_definition_id' => $definition->id,
]);

(new TransformRunner(new TransformValidator))->run($record);
```

## Testing

Feature tests cover:

- multi-source transformation
- validation failure handling
- definition/model guard failures
- model-based validation
- additional custom rules
- source guardrail checks (invalid file references, missing source)

## Example Tinker snippet 
```php
// Tinker example: try in php artisan tinker

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\TransformRunner;
use Moox\Transform\Support\TransformValidator;

final class TinkerDraftMainModel extends BaseDraftModel
{
    protected $table = 'tinker_draft_main_models';

    public $incrementing = true;
    protected $keyType = 'int';

    public string $translationModel = TinkerDraftMainTranslationModel::class;
    public string $translationForeignKey = 'tinker_draft_main_model_id';
    public string $localeKey = 'locale';
    public bool $useTranslationFallback = true;

    protected $fillable = ['status'];

    protected function getCustomTranslatedAttributes(): array
    {
        return ['title'];
    }
}

final class TinkerDraftMainTranslationModel extends BaseDraftTranslationModel
{
    protected $table = 'tinker_draft_main_model_translations';

    protected function getCustomFillable(): array
    {
        return ['tinker_draft_main_model_id', 'title'];
    }
}

$results = [];

$runner = new TransformRunner(new TransformValidator);

$runRecord = function (TransformDefinition $definition, array $recordData = []) use ($runner, &$results) {
    $payload = array_merge([
        'transform_definition_id' => $definition->id,
    ], $recordData);

    // DB check constraint requires at least one non-null source field.
    // If runtime data does not provide one, inherit references from definition.
    if (! array_key_exists('source_projection', $payload) && ! array_key_exists('source_references', $payload)) {
        $payload['source_references'] = $definition->source_references;
    }

    $record = TransformRecord::query()->create($payload);

    $runner->run($record);
    $record->refresh();

    $results[] = [
        'record_id' => $record->id,
        'definition' => $definition->name,
        'status' => $record->status,
        'validation_status' => $record->validation_status,
        'error_message' => $record->error_message,
        'warnings' => $record->warnings,
        'validation_errors' => $record->validation_errors,
    ];

    return $record;
};

$createDefinition = function (array $data) {
    return TransformDefinition::query()->create(array_merge([
        'name' => 'def-' . uniqid(),
        'destination_model' => TinkerDraftMainModel::class,
        'source_references' => [],
        'field_map' => ['title' => 'legacy.title'],
        'validation_rules' => [],
        'is_active' => true,
    ], $data));
};

// Cleanup
Schema::dropIfExists('tinker_draft_main_model_translations');
Schema::dropIfExists('tinker_draft_main_models');
Schema::dropIfExists('legacy_products');
Schema::dropIfExists('legacy_prices');

// IMPORTANT:
// We rely on package migrations for these tables:
// - transform_definitions
// - transform_records
if (! Schema::hasTable('transform_definitions') || ! Schema::hasTable('transform_records')) {
    throw new RuntimeException('Run package migrations first: php artisan migrate');
}

// Optional reset for repeatable local runs (schema stays untouched)
DB::table('transform_records')->delete();
DB::table('transform_definitions')->delete();



// Additional demo tables used by this snippet
Schema::create('tinker_draft_main_models', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->nullable();
    $table->ulid('ulid')->nullable();
    $table->string('status')->nullable();
    $table->softDeletes();
    $table->timestamps();
});
Schema::create('tinker_draft_main_model_translations', function (Blueprint $table): void {
    $table->id();
    $table->unsignedBigInteger('tinker_draft_main_model_id');
    $table->foreign('tinker_draft_main_model_id', 'fk_tdmmt_main_id')
        ->references('id')->on('tinker_draft_main_models')->onDelete('cascade');
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
Schema::create('legacy_products', function (Blueprint $table): void {
    $table->id();
    $table->string('sku')->unique();
    $table->string('title');
    $table->integer('inventory')->nullable();
});
Schema::create('legacy_prices', function (Blueprint $table): void {
    $table->id();
    $table->string('sku')->unique();
    $table->string('label');
});

DB::table('legacy_products')->insert([
    ['sku' => 'P-1', 'title' => 'Switch', 'inventory' => 19],
    ['sku' => 'P-2', 'title' => 'Router', 'inventory' => 7],
]);
DB::table('legacy_prices')->insert([
    ['sku' => 'P-1', 'label' => '9.99 EUR'],
    ['sku' => 'P-2', 'label' => '14.99 EUR'],
]);

File::ensureDirectoryExists(storage_path('app/import'));
File::put(storage_path('app/import/one.json'), json_encode([
    'id' => 77,
    'title' => 'File JSON Title',
], JSON_PRETTY_PRINT));
File::put(storage_path('app/import/items.csv'), "id,title\n101,CSV Alpha\n102,CSV Beta\n");

// 1) SUCCESS: DB source
$defDb = $createDefinition([
    'name' => 'db-only',
    'source_references' => [[
        'source_type' => 'db_table',
        'table' => 'legacy_products',
        'key_column' => 'sku',
        'row_key' => 'P-1',
        'alias' => 'product',
    ]],
    'field_map' => [
        'title' => 'product.title',
        'status' => 'product.inventory',
    ],
]);
$runRecord($defDb);

// 2) SUCCESS: JSON source
$defJson = $createDefinition([
    'name' => 'json-only',
    'source_references' => [[
        'source_type' => 'file_json',
        'path' => storage_path('app/import/one.json'),
        'alias' => 'json',
    ]],
    'field_map' => [
        'title' => 'json.title',
    ],
]);
$runRecord($defJson);

// 3) SUCCESS: CSV source
$defCsv = $createDefinition([
    'name' => 'csv-only',
    'source_references' => [[
        'source_type' => 'file_csv',
        'path' => storage_path('app/import/items.csv'),
        'key_column' => 'id',
        'row_key' => '102',
        'alias' => 'csv',
    ]],
    'field_map' => [
        'title' => 'csv.title',
    ],
]);
$runRecord($defCsv);

// 4) SUCCESS: multiple sources (DB + CSV + projection)
$defMulti = $createDefinition([
    'name' => 'multi-source',
    'source_references' => [
        [
            'source_type' => 'db_table',
            'table' => 'legacy_products',
            'key_column' => 'sku',
            'row_key' => 'P-2',
            'alias' => 'product',
        ],
        [
            'source_type' => 'file_csv',
            'path' => storage_path('app/import/items.csv'),
            'key_column' => 'id',
            'row_key' => '101',
            'alias' => 'csv',
        ],
    ],
    'field_map' => [
        'title' => 'csv.title',
        'status' => 'product.title',
    ],
]);
$runRecord($defMulti, [
    'source_projection' => [
        'legacy' => ['title' => 'Projection Fallback'],
    ],
]);

// 5) FAIL_VALIDATION
$defValidation = $createDefinition([
    'name' => 'validation-fail',
    'source_references' => [],
    'field_map' => [
        'title' => 'legacy.title',
    ],
    'validation_rules' => [
        'title' => ['required', 'string', 'min:10'],
    ],
]);
$runRecord($defValidation, [
    'source_projection' => ['legacy' => ['title' => 'short']],
]);

// 6) FAIL (invalid destination model) -> blocked at definition create
try {
    $createDefinition([
        'name' => 'invalid-model',
        'destination_model' => 'App\\Models\\DoesNotExist',
        'source_references' => [],
        'field_map' => ['title' => 'legacy.title'],
    ]);
} catch (ValidationException $e) {
    $results[] = [
        'record_id' => null,
        'definition' => 'invalid-model',
        'status' => 'definition_rejected',
        'validation_status' => 'invalid',
        'error_message' => $e->getMessage(),
        'warnings' => [],
        'validation_errors' => $e->errors(),
    ];
}

// 7) FAIL (invalid file reference) -> blocked at definition create
try {
    $createDefinition([
        'name' => 'invalid-file',
        'source_references' => [[
            'source_type' => 'file_json',
            'path' => '/no/such/file.json',
            'alias' => 'bad',
        ]],
        'field_map' => ['title' => 'bad.title'],
    ]);
} catch (ValidationException $e) {
    $results[] = [
        'record_id' => null,
        'definition' => 'invalid-file',
        'status' => 'definition_rejected',
        'validation_status' => 'invalid',
        'error_message' => $e->getMessage(),
        'warnings' => [],
        'validation_errors' => $e->errors(),
    ];
}

// Output
[
    'results' => $results,
    'main_table' => DB::table('tinker_draft_main_models')->orderBy('id')->get()->toArray(),
    'translation_table' => DB::table('tinker_draft_main_model_translations')->orderBy('id')->get()->toArray(),
    'transform_records' => DB::table('transform_records')->orderBy('id')->get()->toArray(),
];
```


Run:

```bash
php artisan test --compact packages/transform/tests/Feature/TransformRunnerTest.php
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
