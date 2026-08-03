<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;
use Moox\Media\Traits\HasMediaUsable;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
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

    Schema::create('media_usables', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
        $table->morphs('media_usable');
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

    Schema::create('replace_test_posts', function (Blueprint $table): void {
        $table->id();
        $table->text('featured_media')->nullable();
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('replace_test_posts');
    Schema::dropIfExists('media_translations');
    Schema::dropIfExists('media_usables');
    Schema::dropIfExists('media');
});

function insertReplaceMedia(array $overrides = []): int
{
    return (int) DB::table('media')->insertGetId(array_merge([
        'model_type' => Media::class,
        'model_id' => 1,
        'file_name' => 'old.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'size' => 100,
        'manipulations' => json_encode([]),
        'custom_properties' => json_encode([]),
        'generated_conversions' => json_encode([]),
        'responsive_images' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

function makeReplaceTestPost(?array $featuredMedia = null): Model
{
    $post = new class extends Model
    {
        protected $table = 'replace_test_posts';

        protected $guarded = [];

        public $timestamps = true;
    };

    $post->featured_media = $featuredMedia === null ? null : json_encode($featuredMedia);
    $post->save();

    return $post;
}

it('syncs consumer json metadata to the new file name on replace', function (): void {
    $newMediaId = insertReplaceMedia(['file_name' => 'new.jpg', 'model_id' => 2]);

    $post = makeReplaceTestPost([
        'file_name' => 'old.jpg',
        'title' => 'Hero',
        'alt' => 'Hero alt',
        'description' => null,
        'internal_note' => null,
    ]);

    MediaUsable::query()->create([
        'media_id' => $newMediaId,
        'media_usable_id' => $post->getKey(),
        'media_usable_type' => $post::class,
    ]);

    DB::table('media_translations')->insert([
        'media_id' => $newMediaId,
        'locale' => 'en_US',
        'name' => 'new.jpg',
        'title' => 'Hero',
        'alt' => 'Hero alt',
        'description' => null,
        'internal_note' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $newMedia = Media::query()->findOrFail($newMediaId);
    HasMediaUsable::syncMediaMetadata($newMedia, 'old.jpg');

    $post->refresh();
    $payload = json_decode((string) $post->featured_media, true);

    expect($payload['file_name'])->toBe('new.jpg')
        ->and($payload['title'])->toBe('Hero');
});

it('cascades media_usables when media row is deleted', function (): void {
    $mediaId = insertReplaceMedia(['model_id' => 1]);
    $post = makeReplaceTestPost();

    MediaUsable::query()->create([
        'media_id' => $mediaId,
        'media_usable_id' => $post->getKey(),
        'media_usable_type' => $post::class,
    ]);

    DB::table('media')->where('id', $mediaId)->delete();

    expect(MediaUsable::query()->where('media_id', $mediaId)->exists())->toBeFalse();
});

it('moves media_usables to the replacement media id before old cleanup', function (): void {
    $oldMediaId = insertReplaceMedia(['file_name' => 'old.jpg', 'model_id' => 1]);
    $newMediaId = insertReplaceMedia(['file_name' => 'new.jpg', 'model_id' => 2]);
    $post = makeReplaceTestPost(['file_name' => 'old.jpg', 'title' => 'Hero']);

    MediaUsable::query()->create([
        'media_id' => $oldMediaId,
        'media_usable_id' => $post->getKey(),
        'media_usable_type' => $post::class,
    ]);

    // Same sequence as MediaResource replace transaction (copy, then detach old).
    $usables = DB::table('media_usables')->where('media_id', $oldMediaId)->get();
    foreach ($usables as $usable) {
        DB::table('media_usables')->insert([
            'media_id' => $newMediaId,
            'media_usable_id' => $usable->media_usable_id,
            'media_usable_type' => $usable->media_usable_type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    DB::table('media_usables')->where('media_id', $oldMediaId)->delete();

    expect(MediaUsable::query()->where('media_id', $oldMediaId)->exists())->toBeFalse()
        ->and(MediaUsable::query()->where('media_id', $newMediaId)->count())->toBe(1)
        ->and((int) MediaUsable::query()->where('media_id', $newMediaId)->value('media_usable_id'))
        ->toBe((int) $post->getKey());
});
