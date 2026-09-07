<?php

declare(strict_types=1);

require_once __DIR__.'/../Support/MediaTestingDatabase.php';

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Media\Http\Livewire\MediaPickerModal;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Models\MediaTranslation;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    configureMediaTestingDatabase();
    config(['app.locale' => 'en', 'app.fallback_locale' => 'en']);

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

    Schema::create('media_translations', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
        $table->string('locale')->index();
        $table->string('name');
        $table->string('title')->nullable();
        $table->string('alt')->nullable();
        $table->text('description')->nullable();
        $table->text('internal_note')->nullable();
        $table->timestamps();
        $table->unique(['media_id', 'locale']);
    });

    Schema::create('media_usables', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
        $table->morphs('media_usable');
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('media_usables');
    Schema::dropIfExists('media_translations');
    Schema::dropIfExists('media');
    Schema::dropIfExists('media_collection_translations');
    Schema::dropIfExists('media_collections');
});

function insertPickerMetadataMedia(array $overrides = []): int
{
    return (int) DB::table('media')->insertGetId(array_merge([
        'model_type' => Media::class,
        'model_id' => 1,
        'file_name' => 'hero.jpg',
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

function insertPickerMetadataTranslation(int $mediaId, string $locale, array $values = []): void
{
    DB::table('media_translations')->insert(array_merge([
        'media_id' => $mediaId,
        'locale' => $locale,
        'name' => 'hero.jpg',
        'title' => null,
        'alt' => null,
        'description' => null,
        'internal_note' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $values));
}

function createPickerMetadataCollection(string $name): MediaCollection
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

function pickerModalForMedia(int $mediaId, array $meta = []): MediaPickerModal
{
    $modal = new MediaPickerModal;
    $modal->lang = 'en_US';
    $modal->selectedMediaMeta = array_merge([
        'id' => $mediaId,
        'file_name' => 'hero.jpg',
        'name' => '',
        'title' => '',
        'description' => '',
        'internal_note' => '',
        'alt' => '',
        'write_protected' => false,
        'media_collection_id' => null,
        'collection_name' => '',
    ], $meta);

    return $modal;
}

it('saves picker metadata to the switcher locale translation', function (): void {
    $mediaId = insertPickerMetadataMedia();

    $modal = pickerModalForMedia($mediaId);
    $modal->updatedSelectedMediaMeta('Hero title', 'title');
    $modal->updatedSelectedMediaMeta('Hero alt', 'alt');
    $modal->updatedSelectedMediaMeta('Hero description', 'description');
    $modal->updatedSelectedMediaMeta('Internal', 'internal_note');
    $modal->updatedSelectedMediaMeta('hero-name', 'name');

    $translation = MediaTranslation::query()
        ->where('media_id', $mediaId)
        ->where('locale', 'en_US')
        ->first();

    expect($translation)->not->toBeNull()
        ->and($translation->name)->toBe('hero-name')
        ->and($translation->title)->toBe('Hero title')
        ->and($translation->alt)->toBe('Hero alt')
        ->and($translation->description)->toBe('Hero description')
        ->and($translation->internal_note)->toBe('Internal')
        ->and(MediaTranslation::query()->where('media_id', $mediaId)->count())->toBe(1);
});

it('does not overwrite translations in other locales when saving picker metadata', function (): void {
    $mediaId = insertPickerMetadataMedia();

    insertPickerMetadataTranslation($mediaId, 'de_DE', [
        'name' => 'de-name',
        'title' => 'Deutscher Titel',
        'alt' => 'Deutscher Alt',
        'description' => 'Deutsche Beschreibung',
        'internal_note' => 'Interne Notiz',
    ]);

    pickerModalForMedia($mediaId)->updatedSelectedMediaMeta('English title', 'title');

    expect(MediaTranslation::query()->where('media_id', $mediaId)->where('locale', 'de_DE')->first())
        ->title->toBe('Deutscher Titel')
        ->and(MediaTranslation::query()->where('media_id', $mediaId)->where('locale', 'en_US')->first())
        ->title->toBe('English title');
});

it('persists collection changes from the picker', function (): void {
    $source = createPickerMetadataCollection('Inbox');
    $target = createPickerMetadataCollection('Marketing');
    $mediaId = insertPickerMetadataMedia([
        'media_collection_id' => $source->getKey(),
        'collection_name' => 'Inbox',
        'model_id' => 1,
    ]);

    $modal = pickerModalForMedia($mediaId, [
        'media_collection_id' => $source->getKey(),
        'collection_name' => 'Inbox',
    ]);
    $modal->updatedSelectedMediaMeta($target->getKey(), 'media_collection_id');

    $media = Media::query()->findOrFail($mediaId);

    expect($media->media_collection_id)->toBe($target->getKey())
        ->and($media->collection_name)->toBe('Marketing')
        ->and($modal->selectedMediaMeta['media_collection_id'])->toBe($target->getKey())
        ->and($modal->selectedMediaMeta['collection_name'])->toBe('Marketing');
});

it('does not persist metadata or collection changes for write-protected media', function (): void {
    $source = createPickerMetadataCollection('Inbox');
    $target = createPickerMetadataCollection('Marketing');
    $mediaId = insertPickerMetadataMedia([
        'write_protected' => true,
        'media_collection_id' => $source->getKey(),
        'collection_name' => 'Inbox',
    ]);

    insertPickerMetadataTranslation($mediaId, 'en_US', [
        'name' => 'locked',
        'title' => 'Original',
        'alt' => 'Original alt',
        'description' => 'Original description',
        'internal_note' => 'Original note',
    ]);

    $modal = pickerModalForMedia($mediaId, [
        'write_protected' => true,
        'media_collection_id' => $source->getKey(),
        'collection_name' => 'Inbox',
    ]);
    $modal->updatedSelectedMediaMeta('Tampered', 'title');
    $modal->updatedSelectedMediaMeta($target->getKey(), 'media_collection_id');

    $media = Media::query()->findOrFail($mediaId);
    $translation = MediaTranslation::query()
        ->where('media_id', $mediaId)
        ->where('locale', 'en_US')
        ->first();

    expect($translation->title)->toBe('Original')
        ->and($media->media_collection_id)->toBe($source->getKey())
        ->and($media->collection_name)->toBe('Inbox');
});
