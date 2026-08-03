<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaCollection;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Schema::create('media_collections', function (Blueprint $table): void {
        $table->id();
        $table->timestamps();
    });

    Schema::create('media_collection_translations', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('media_collection_id')->constrained('media_collections')->cascadeOnDelete();
        $table->string('locale')->index();
        $table->string('name')->nullable();
        $table->text('description')->nullable();
        $table->timestamps();
        $table->unique(['media_collection_id', 'locale']);
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
    Schema::dropIfExists('media_collection_translations');
    Schema::dropIfExists('media_collections');
});

function createNamedCollection(string $name): MediaCollection
{
    $collection = MediaCollection::query()->create([]);
    $collection->translateOrNew('en_US')->fill([
        'name' => $name,
        'description' => $name,
    ]);
    $collection->save();
    $collection->load('translations');

    return $collection;
}

function insertMediaRow(array $overrides = []): int
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
        'write_protected' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

it('blocks deleting the uncategorized collection', function (): void {
    $uncategorized = createNamedCollection('Uncategorized');

    expect($uncategorized->delete())->toBeFalse()
        ->and(MediaCollection::query()->whereKey($uncategorized->getKey())->exists())->toBeTrue();
});

it('reassigns media to uncategorized when a collection is deleted', function (): void {
    config(['app.locale' => 'en']);

    $uncategorized = createNamedCollection('Uncategorized');
    $marketing = createNamedCollection('Marketing');

    $mediaId = insertMediaRow([
        'media_collection_id' => $marketing->getKey(),
        'collection_name' => 'Marketing',
        'model_id' => 1,
    ]);

    expect($marketing->delete())->not->toBeFalse();

    $media = DB::table('media')->where('id', $mediaId)->first();

    expect($media)->not->toBeNull()
        ->and((int) $media->media_collection_id)->toBe((int) $uncategorized->getKey())
        ->and(MediaCollection::query()->whereKey($marketing->getKey())->exists())->toBeFalse();
});

it('blocks deleting a collection that contains write-protected media', function (): void {
    $marketing = createNamedCollection('Marketing');

    insertMediaRow([
        'media_collection_id' => $marketing->getKey(),
        'collection_name' => 'Marketing',
        'write_protected' => true,
        'model_id' => 1,
    ]);

    expect($marketing->delete())->toBeFalse()
        ->and(MediaCollection::query()->whereKey($marketing->getKey())->exists())->toBeTrue();
});

it('throws when deleting write-protected media', function (): void {
    $mediaId = insertMediaRow([
        'write_protected' => true,
        'model_id' => 1,
    ]);

    $media = Media::query()->findOrFail($mediaId);

    expect(fn () => $media->delete())
        ->toThrow(Exception::class);
});

it('throws when saving changes to write-protected media', function (): void {
    $mediaId = insertMediaRow([
        'write_protected' => true,
        'file_name' => 'locked.jpg',
        'model_id' => 1,
    ]);

    $media = Media::query()->findOrFail($mediaId);
    $media->file_name = 'changed.jpg';

    expect(fn () => $media->save())
        ->toThrow(Exception::class);
});
