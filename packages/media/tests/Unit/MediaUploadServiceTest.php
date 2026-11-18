<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Moox\Media\Models\Media;
use Moox\Media\Support\MediaUploadOptions;
use Moox\Media\Support\MediaUploadResult;
use Moox\Media\Support\MediaUploadService;
use Moox\Media\Support\MediaUploadStatus;
use Tests\TestCase;

require_once __DIR__.'/../Support/MediaTestingDatabase.php';

uses(TestCase::class);

beforeEach(function (): void {
    configureMediaTestingDatabase();

    config([
        'media.upload.resource.accepted_file_types' => [
            'image/*',
            'application/pdf',
        ],
    ]);

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
        $table->string('name')->nullable();
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
        $table->string('name')->nullable();
        $table->string('title')->nullable();
        $table->string('alt')->nullable();
        $table->text('description')->nullable();
        $table->text('internal_note')->nullable();
        $table->timestamps();
        $table->unique(['media_id', 'locale']);
    });
});

afterEach(function (): void {
    Schema::dropIfExists('media_translations');
    Schema::dropIfExists('media');
    Schema::dropIfExists('media_collection_translations');
    Schema::dropIfExists('media_collections');
});

function insertHashedMedia(string $fileName, string $fileHash): int
{
    $mediaId = (int) DB::table('media')->insertGetId([
        'model_type' => Media::class,
        'model_id' => 1,
        'file_name' => $fileName,
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'size' => 100,
        'manipulations' => json_encode([]),
        'custom_properties' => json_encode(['file_hash' => $fileHash]),
        'generated_conversions' => json_encode([]),
        'responsive_images' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('media_translations')->insert([
        'media_id' => $mediaId,
        'locale' => 'en_US',
        'name' => $fileName,
        'title' => pathinfo($fileName, PATHINFO_FILENAME),
        'alt' => pathinfo($fileName, PATHINFO_FILENAME),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $mediaId;
}

it('detects duplicates by content hash without creating a new media row', function (): void {
    $file = UploadedFile::fake()->image('hero.jpg');
    $hash = hash_file('sha256', $file->getRealPath());
    expect($hash)->toBeString();

    $existingId = insertHashedMedia('hero.jpg', $hash);

    $result = app(MediaUploadService::class)->upload($file, new MediaUploadOptions(
        locale: 'en_US',
        useAuthenticatedUploader: false,
    ));

    expect($result->isDuplicate())->toBeTrue()
        ->and($result->status)->toBe(MediaUploadStatus::Duplicate)
        ->and($result->existingMedia?->getKey())->toBe($existingId)
        ->and($result->fileHash)->toBe($hash)
        ->and(Media::query()->count())->toBe(1);
});

it('detects duplicates by translation name', function (): void {
    $existing = UploadedFile::fake()->image('same-name.jpg');
    $existingHash = hash_file('sha256', $existing->getRealPath());
    insertHashedMedia('same-name.jpg', (string) $existingHash);

    // Different bytes, same client name → still duplicate via translation name.
    $file = UploadedFile::fake()->image('same-name.jpg', 20, 20);

    $result = app(MediaUploadService::class)->upload($file, new MediaUploadOptions(
        locale: 'en_US',
        useAuthenticatedUploader: false,
    ));

    expect($result->isDuplicate())->toBeTrue()
        ->and($result->fileName)->toBe('same-name.jpg')
        ->and(Media::query()->count())->toBe(1);
});

it('rejects disallowed mime types before persistence', function (): void {
    $file = UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload');

    expect(fn () => app(MediaUploadService::class)->upload($file, new MediaUploadOptions(
        locale: 'en_US',
        useAuthenticatedUploader: false,
    )))->toThrow(ValidationException::class);

    expect(Media::query()->count())->toBe(0);
});

it('honours an explicit accepted_file_types allowlist', function (): void {
    $file = UploadedFile::fake()->image('photo.png');

    expect(fn () => app(MediaUploadService::class)->upload($file, new MediaUploadOptions(
        locale: 'en_US',
        acceptedFileTypes: ['application/pdf'],
        useAuthenticatedUploader: false,
    )))->toThrow(ValidationException::class);
});

it('exposes success helpers on MediaUploadResult', function (): void {
    $created = new MediaUploadResult(MediaUploadStatus::Created, media: new Media);
    $duplicate = new MediaUploadResult(MediaUploadStatus::Duplicate, existingMedia: new Media);
    $replaced = new MediaUploadResult(MediaUploadStatus::Replaced, media: new Media);
    $edited = new MediaUploadResult(MediaUploadStatus::Edited, media: new Media);

    expect($created->succeeded())->toBeTrue()
        ->and($created->wasCreated())->toBeTrue()
        ->and($duplicate->succeeded())->toBeFalse()
        ->and($duplicate->isDuplicate())->toBeTrue()
        ->and($replaced->wasReplaced())->toBeTrue()
        ->and($edited->wasEdited())->toBeTrue();
});

it('applies scopeQuery when looking for duplicates', function (): void {
    $file = UploadedFile::fake()->image('scoped.jpg');
    $hash = (string) hash_file('sha256', $file->getRealPath());

    $otherScopeId = insertHashedMedia('scoped.jpg', $hash);
    DB::table('media')->where('id', $otherScopeId)->update(['scope' => 'other']);

    $service = app(MediaUploadService::class);

    $outsideScope = $service->findDuplicate(
        'scoped.jpg',
        $hash,
        fn ($query) => $query->where('scope', 'mine'),
    );

    $insideScope = $service->findDuplicate(
        'scoped.jpg',
        $hash,
        fn ($query) => $query->where('scope', 'other'),
    );

    expect($outsideScope)->toBeNull()
        ->and($insideScope?->getKey())->toBe($otherScopeId);
});

it('can exclude a media id from duplicate detection (replace self)', function (): void {
    $file = UploadedFile::fake()->image('self.jpg');
    $hash = (string) hash_file('sha256', $file->getRealPath());
    $mediaId = insertHashedMedia('self.jpg', $hash);

    $service = app(MediaUploadService::class);

    expect($service->findDuplicate('self.jpg', $hash))->not->toBeNull()
        ->and($service->findDuplicate('self.jpg', $hash, excludeMediaId: $mediaId))->toBeNull();
});
