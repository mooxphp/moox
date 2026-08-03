<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\Media\Models\Media;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Schema::create('media_collections', function (Blueprint $table): void {
        $table->id();
        $table->timestamps();
    });

    Schema::create('media', function (Blueprint $table): void {
        $table->id();
        $table->nullableMorphs('model');
        $table->nullableMorphs('uploader');
        $table->uuid()->nullable()->unique();
        $table->nullableMorphs('original_model');
        $table->unsignedBigInteger('media_collection_id')->nullable();
        $table->string('collection_name')->nullable();
        $table->string('file_name');
        $table->string('mime_type')->nullable();
        $table->boolean('write_protected')->default(false);
        $table->string('disk');
        $table->string('conversions_disk')->nullable();
        $table->unsignedBigInteger('size')->default(0);
        $table->json('manipulations');
        $table->json('custom_properties');
        $table->json('generated_conversions');
        $table->json('responsive_images');
        $table->unsignedInteger('order_column')->nullable();
        $table->string('scope')->nullable()->index();
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('media');
    Schema::dropIfExists('media_collections');
});

function insertTestMedia(array $overrides = []): int
{
    return (int) DB::table('media')->insertGetId(array_merge([
        'model_type' => Media::class,
        'model_id' => 1,
        'file_name' => 'file.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'size' => 100,
        'manipulations' => json_encode([]),
        'custom_properties' => json_encode([]),
        'generated_conversions' => json_encode([]),
        'responsive_images' => json_encode([]),
        'scope' => 'media:default:private',
        'media_collection_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

function resolveAuthorizedMediaIds(MediaPicker $picker, Model $record, array $mediaIds): array
{
    $method = new ReflectionMethod(MediaPicker::class, 'resolveAuthorizedMedia');

    /** @var Collection<int, Media> $result */
    $result = $method->invoke($picker, $record, $mediaIds);

    return $result->modelKeys();
}

it('returns only media that match the scoped media scope', function (): void {
    $allowedId = insertTestMedia(['scope' => 'media:draft:a:private', 'file_name' => 'a.jpg']);
    $deniedId = insertTestMedia(['scope' => 'media:draft:b:private', 'file_name' => 'b.jpg']);

    $record = new class extends Model
    {
        protected $guarded = [];
    };
    $record->id = 1;

    $picker = MediaPicker::make('attachments')
        ->scopedMediaScope('media:draft:a:private');

    $authorizedIds = resolveAuthorizedMediaIds($picker, $record, [$allowedId, $deniedId]);

    expect($authorizedIds)->toBe([$allowedId])
        ->and(count($authorizedIds))->not->toBe(2);
});

it('filters by scoped media collection id', function (): void {
    $collectionId = (int) DB::table('media_collections')->insertGetId([
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $inCollection = insertTestMedia([
        'media_collection_id' => $collectionId,
        'scope' => null,
    ]);
    $outOfCollection = insertTestMedia([
        'media_collection_id' => null,
        'scope' => null,
    ]);

    $record = new class extends Model
    {
        protected $guarded = [];
    };
    $record->id = 1;

    $picker = MediaPicker::make('attachments')
        ->scopedMediaCollectionId($collectionId);

    expect(resolveAuthorizedMediaIds($picker, $record, [$inCollection, $outOfCollection]))
        ->toBe([$inCollection]);
});
