<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Support\ConfiguredImportRecordPayloadReader;
use Moox\Transform\Support\ConfiguredLocaleVariantResolver;
use Moox\Transform\Support\Execution\BatchDestinationWriterRegistry;
use Moox\Transform\Support\Execution\BulkTransformExecutor;
use Moox\Transform\Support\Execution\EloquentUpsertBatchDestinationWriter;
use Moox\Transform\Support\Execution\ResolvedTransformDataFactory;
use Moox\Transform\Support\Execution\TranslatableBatchDestinationWriter;
use Moox\Transform\Support\Expansion\ExpandTransformExecutor;
use Moox\Transform\Support\Expansion\TransformProjectionExpander;
use Moox\Transform\Support\Operations\InlineOperationRegistry;
use Moox\Transform\Support\SourceContextResolver;
use Moox\Transform\Support\SourcePayloadResolver;
use Moox\Transform\Support\TemplateValueResolver;
use Moox\Transform\Support\TransformRunner;
use Moox\Transform\Support\TransformValidator;

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
 * @property string|null $code
 * @property array<string, mixed>|null $meta
 */
final class TransformJsonDummyModel extends Model
{
    protected $table = 'transform_json_dummy_models';

    protected $fillable = [
        'code',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }
}

/**
 * @property string|null $title
 * @property string|null $external_reference
 */
final class TransformSoftDeleteDummyModel extends Model
{
    use SoftDeletes;

    protected $table = 'transform_soft_delete_dummy_models';

    protected $fillable = [
        'title',
        'external_reference',
    ];
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

/**
 * @property string|null $code
 */
final class TransformDraftBatchModel extends BaseDraftModel
{
    protected $table = 'transform_draft_batch_models';

    public $incrementing = true;

    protected $keyType = 'int';

    public string $translationModel = TransformDraftBatchTranslationModel::class;

    public string $translationForeignKey = 'transform_draft_batch_model_id';

    public string $localeKey = 'locale';

    public bool $useTranslationFallback = true;

    protected $fillable = [
        'code',
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
final class TransformDraftBatchTranslationModel extends BaseDraftTranslationModel
{
    protected $table = 'transform_draft_batch_model_translations';

    /**
     * @return array<int, string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'transform_draft_batch_model_id',
            'title',
        ];
    }
}

function assertTransformTestsUseSafeDatabase(): void
{
    $connection = (string) config('database.default');
    $database = (string) config("database.connections.{$connection}.database");

    if ($connection !== 'sqlite' || $database !== ':memory:') {
        throw new RuntimeException(
            "Transform tests refused to run against [{$connection}:{$database}]. "
            .'Package tests must use sqlite :memory: only.'
        );
    }
}

function createTestTables(): void
{
    assertTransformTestsUseSafeDatabase();

    Schema::dropIfExists('transform_soft_delete_dummy_models');
    Schema::dropIfExists('transform_dummy_models');
    Schema::dropIfExists('transform_json_dummy_models');
    Schema::dropIfExists('transform_draft_main_model_translations');
    Schema::dropIfExists('transform_draft_main_models');
    Schema::dropIfExists('transform_draft_batch_model_translations');
    Schema::dropIfExists('transform_draft_batch_models');
    Schema::dropIfExists('transform_records');
    Schema::dropIfExists('transform_definitions');

    Schema::create('transform_definitions', function (Blueprint $table): void {
        $table->id();
        $table->timestamps();
        $table->softDeletes();
        $table->string('name')->unique();
        $table->string('destination_model');
        $table->json('destination_match')->nullable();
        $table->json('source_references');
        $table->json('field_map');
        $table->json('validation_rules')->nullable();
        $table->string('execution_mode')->default('single');
        $table->json('expand')->nullable();
        $table->json('bulk')->nullable();
        $table->boolean('is_active')->default(true);
    });

    Schema::create('transform_records', function (Blueprint $table): void {
        $table->id();
        $table->timestamps();
        $table->softDeletes();
        $table->foreignId('transform_definition_id')->constrained('transform_definitions');
        $table->foreignId('parent_transform_record_id')->nullable()->constrained('transform_records')->nullOnDelete();
        $table->string('destination_key')->nullable();
        $table->json('source_projection')->nullable();
        $table->json('source_references')->nullable();
        $table->string('input_hash', 64)->nullable();
        $table->string('status')->default('pending');
        $table->string('validation_status')->default('pending');
        $table->json('validation_errors')->nullable();
        $table->json('warnings')->nullable();
        $table->json('bulk_stats')->nullable();
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
        $table->string('price_label')->nullable()->unique();
        $table->timestamps();
    });

    Schema::create('transform_json_dummy_models', function (Blueprint $table): void {
        $table->id();
        $table->string('code')->nullable();
        $table->json('meta')->nullable();
        $table->timestamps();
    });

    Schema::create('transform_soft_delete_dummy_models', function (Blueprint $table): void {
        $table->id();
        $table->string('title')->nullable();
        $table->string('external_reference')->nullable()->unique();
        $table->timestamps();
        $table->softDeletes();
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
        $table->unsignedBigInteger('transform_draft_main_model_id');
        $table->foreign('transform_draft_main_model_id', 'tdmmt_main_id_fk')
            ->references('id')
            ->on('transform_draft_main_models')
            ->cascadeOnDelete();
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

    Schema::create('transform_draft_batch_models', function (Blueprint $table): void {
        $table->id();
        $table->uuid('uuid')->nullable();
        $table->ulid('ulid')->nullable();
        $table->string('code')->unique();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('transform_draft_batch_model_translations', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('transform_draft_batch_model_id');
        $table->foreign('transform_draft_batch_model_id', 'tdbmt_main_id_fk')
            ->references('id')
            ->on('transform_draft_batch_models')
            ->cascadeOnDelete();
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
        $table->unique(['transform_draft_batch_model_id', 'locale'], 'tdbmt_main_locale_unique');
    });
}

function makeRunner(): TransformRunner
{
    $inlineOperationRegistry = new InlineOperationRegistry;
    $templateValueResolver = new TemplateValueResolver;
    $importRecordPayloadReader = new ConfiguredImportRecordPayloadReader;
    $localeVariantResolver = new ConfiguredLocaleVariantResolver;
    $sourcePayloadResolver = new SourcePayloadResolver($importRecordPayloadReader, $templateValueResolver);
    $projectionExpander = new TransformProjectionExpander(
        $sourcePayloadResolver,
        $importRecordPayloadReader,
        $localeVariantResolver,
        $templateValueResolver,
    );
    $resolvedTransformDataFactory = new ResolvedTransformDataFactory(
        $inlineOperationRegistry,
        new SourceContextResolver,
    );
    $batchDestinationWriterRegistry = new BatchDestinationWriterRegistry([
        new TranslatableBatchDestinationWriter,
        new EloquentUpsertBatchDestinationWriter,
    ]);

    return new TransformRunner(
        new TransformValidator,
        $sourcePayloadResolver,
        $projectionExpander,
        new ExpandTransformExecutor($projectionExpander),
        new BulkTransformExecutor,
        $resolvedTransformDataFactory,
        $batchDestinationWriterRegistry,
    );
}

/**
 * @param  array<string, mixed>  $overrides
 */
function createDefinition(array $overrides = []): TransformDefinition
{
    $defaults = [
        'name' => 'Test Definition',
        'destination_model' => TransformDummyModel::class,
        'destination_match' => [
            'title' => 'legacy.title',
        ],
        'source_references' => [],
        'field_map' => [
            'title' => 'legacy.title',
        ],
        'validation_rules' => [],
        'is_active' => true,
    ];

    return TransformDefinition::query()->create([...$defaults, ...$overrides]);
}

function createFilamentTestUser(): User
{
    if (! Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /** @var User $user */
    $user = User::query()->create([
        'name' => 'Transform Test User',
        'email' => 'transform-test-'.uniqid().'@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('password'),
    ]);

    return $user;
}
