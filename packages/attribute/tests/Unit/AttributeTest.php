<?php

use Carbon\Carbon;
use Moox\Draft\Models\Draft;

beforeEach(function () {
    $this->user = $this->createTestUser();
    $this->actingAs($this->user);
});

test('draft can be created with real data in different languages', function () {
    $user = $this->user;
    $draft = Draft::create([
        'is_active' => true,
        'image' => ['url' => 'https://example.com/image.jpg', 'alt' => 'Test image'],
        'type' => 'article',
        'color' => '#ff5733',
        'due_at' => Carbon::now()->addDays(7),
        'status' => 'draft',
        'custom_properties' => ['theme' => 'dark', 'layout' => 'grid'],
        'title' => 'English Title',
        'slug' => 'english-title',
        'permalink' => 'https://example.com/permalink',
        'description' => 'English description',
        'content' => 'English content here',
        'author_id' => 1,
        'author_type' => $user::class,
    ]);

    $draft->createTranslation('de', [
        'title' => 'Deutscher Titel',
        'slug' => 'deutscher-titel',
        'permalink' => 'https://example.com/permalink-de',
        'description' => 'Deutsche Beschreibung',
        'content' => 'Deutscher Inhalt hier',
        'author_id' => 1,
        'author_type' => $user::class,
    ]);
    $draft->createTranslation('es', [
        'title' => 'Español Titel',
        'slug' => 'español-titel',
        'permalink' => 'https://example.com/permalink-es',
        'description' => 'Español Beschreibung',
        'content' => 'Español Inhalt hier',
        'author_id' => 1,
        'author_type' => $user::class,
    ]);

    expect($draft)->toBeInstanceOf(Draft::class);
    expect($draft->is_active)->toBeTrue();
    expect($draft->type)->toBe('article');
    expect($draft->color)->toBe('#ff5733');
    expect($draft->due_at)->toBeInstanceOf(Carbon::class);
    expect($draft->status)->toBe('draft');
    expect($draft->custom_properties)->toBe(['theme' => 'dark', 'layout' => 'grid']);
    expect($draft->image)->toBe(['url' => 'https://example.com/image.jpg', 'alt' => 'Test image']);
    expect($draft->permalink)->toBe('https://example.com/permalink');
    expect($draft->title)->toBe('English Title');
    expect($draft->slug)->toBe('english-title');
    expect($draft->description)->toBe('English description');
    expect($draft->content)->toBe('English content here');
    expect($draft->author_id)->toBe(1);
    expect($draft->author_type)->toBe($user::class);
    expect($draft->hasTranslation('en'))->toBeTrue();
    expect($draft->hasTranslation('de'))->toBeTrue();
    expect($draft->hasTranslation('es'))->toBeTrue();
    expect($draft->getAvailableTranslations())->toContain('en', 'de', 'es');
    expect($draft->translate('en')->title)->toBe('English Title');
    expect($draft->translate('de')->title)->toBe('Deutscher Titel');
    expect($draft->translate('es')->title)->toBe('Español Titel');
    expect($draft->translate('en')->slug)->toBe('english-title');
    expect($draft->translate('de')->slug)->toBe('deutscher-titel');
    expect($draft->translate('es')->slug)->toBe('español-titel');
    expect($draft->translate('en')->permalink)->toBe('https://example.com/permalink');
    expect($draft->translate('de')->permalink)->toBe('https://example.com/permalink-de');
    expect($draft->translate('es')->permalink)->toBe('https://example.com/permalink-es');
    expect($draft->translate('en')->description)->toBe('English description');
    expect($draft->translate('de')->description)->toBe('Deutsche Beschreibung');
    expect($draft->translate('es')->description)->toBe('Español Beschreibung');
    expect($draft->translate('en')->content)->toBe('English content here');
    expect($draft->translate('de')->content)->toBe('Deutscher Inhalt hier');
    expect($draft->translate('es')->content)->toBe('Español Inhalt hier');
    expect($draft->translate('en')->author_id)->toBe(1);
    expect($draft->translate('de')->author_id)->toBe(1);
    expect($draft->translate('es')->author_id)->toBe(1);
    expect($draft->translate('en')->author_type)->toBe($user::class);
    expect($draft->translate('de')->author_type)->toBe($user::class);
    expect($draft->translate('es')->author_type)->toBe($user::class);
});

test('draft can be updated', function () {
    $draft = Draft::factory()->create([
        'type' => 'article',
        'color' => '#ff0000',
        'status' => 'draft',
    ]);

    $draft->update([
        'type' => 'page',
        'color' => '#00ff00',
        'status' => 'published',
    ]);

    $draft->refresh();

    expect($draft->type)->toBe('page');
    expect($draft->color)->toBe('#00ff00');
    expect($draft->status)->toBe('published');
});

test('draft translations can be soft deleted', function () {
    $draft = Draft::factory()->create();

    expect($draft->getTranslationsArray())->toHaveCount(4);

    $draft->deleteTranslations('de');
    expect($draft->getTranslationsArray())->toHaveCount(3);
    expect($draft->hasTranslation('de'))->toBeFalse();

    $draft->deleteTranslations('es');
    expect($draft->getTranslationsArray())->toHaveCount(2);
    expect($draft->hasTranslation('es'))->toBeFalse();

    $draft->deleteTranslations('fr');
    expect($draft->getTranslationsArray())->toHaveCount(1);
    expect($draft->hasTranslation('fr'))->toBeFalse();

    $draft->deleteTranslations('en');
    expect($draft->getTranslationsArray())->toHaveCount(0);
    expect($draft->hasTranslation('en'))->toBeFalse();

    $draft->checkAndDeleteIfAllTranslationsDeleted();
    expect($draft->trashed())->toBeTrue();
});

// test('draft can be restored from soft delete', function () {
//     $draft = Draft::factory()->create();
//     $draftId = $draft->id;

//     $draft->delete();
//     expect($draft->trashed())->toBeTrue();

//     $draft->restore();
//     $draft->refresh();

//     expect($draft->trashed())->toBeFalse();
//     expect(Draft::find($draftId))->not->toBeNull();
// });

// test('draft can be force deleted', function () {
//     $draft = Draft::factory()->create();
//     $draftId = $draft->id;

//     $draft->forceDelete();

//     expect(Draft::withTrashed()->find($draftId))->toBeNull();
// });

// test('draft can store real media file', function () {
//     Storage::fake('public');

//     $draft = Draft::factory()->create();
//     $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

//     $media = $draft->addMediaFromRequest('file')
//         ->usingName('Test Image')
//         ->usingFileName('test-image.jpg')
//         ->toMediaCollection('images');

//     expect($draft->media)->toHaveCount(1);
//     expect($media->name)->toBe('Test Image');
//     expect($media->collection_name)->toBe('images');

//     // Test media conversion
//     $draft->registerMediaConversions($media);
//     expect($media->getUrl('preview'))->toBeString();
// });

// test('draft can create translation', function () {
//     $draft = Draft::factory()->create();

//     // Create English translation
//     $draft->createTranslation('en', [
//         'title' => 'English Title',
//         'slug' => 'english-title',
//         'description' => 'English description',
//         'content' => 'English content here',
//         'author_id' => 1,
//         'author_type' => User::class
//     ]);

//     // Create German translation
//     $draft->createTranslation('de', [
//         'title' => 'Deutscher Titel',
//         'slug' => 'deutscher-titel',
//         'description' => 'Deutsche Beschreibung',
//         'content' => 'Deutscher Inhalt hier',
//         'author_id' => 1,
//         'author_type' => User::class
//     ]);

//     expect($draft->hasTranslation('en'))->toBeTrue();
//     expect($draft->hasTranslation('de'))->toBeTrue();
//     expect($draft->getAvailableTranslations())->toContain('en', 'de');
// });

// test('draft translation can be updated', function () {
//     $draft = Draft::factory()->create();

//     $draft->createTranslation('en', [
//         'title' => 'Original Title',
//         'content' => 'Original content'
//     ]);

//     // Update translation
//     $draft->createTranslation('en', [
//         'title' => 'Updated Title',
//         'content' => 'Updated content',
//         'description' => 'New description'
//     ]);

//     $translation = $draft->translate('en');
//     expect($translation->title)->toBe('Updated Title');
//     expect($translation->content)->toBe('Updated content');
//     expect($translation->description)->toBe('New description');
// });

// test('draft translation can be deleted', function () {
//     $draft = Draft::factory()->create();

//     $draft->createTranslation('en', ['title' => 'English Title']);
//     $draft->createTranslation('de', ['title' => 'Deutscher Titel']);

//     expect($draft->hasTranslation('en'))->toBeTrue();
//     expect($draft->hasTranslation('de'))->toBeTrue();

//     $draft->deleteTranslation('en');

//     expect($draft->hasTranslation('en'))->toBeFalse();
//     expect($draft->hasTranslation('de'))->toBeTrue();
// });

// test('draft with media and translations complete workflow', function () {
//     Storage::fake('public');

//     // Create draft with real data
//     $draft = Draft::create([
//         'is_active' => true,
//         'type' => 'article',
//         'status' => 'draft',
//         'data' => ['category' => 'technology'],
//         'color' => '#3498db'
//     ]);

//     // Add media
//     $file = UploadedFile::fake()->image('article-image.jpg');
//     $media = $draft->addMediaFromRequest('file')
//         ->usingName('Article Image')
//         ->toMediaCollection('images');

//     // Create translations
//     $draft->createTranslation('en', [
//         'title' => 'How to Build APIs',
//         'slug' => 'how-to-build-apis',
//         'content' => 'This is a comprehensive guide...',
//         'author_id' => 1,
//         'author_type' => $this->createTestUser()::class
//     ]);

//     $draft->createTranslation('de', [
//         'title' => 'Wie man APIs baut',
//         'slug' => 'wie-man-apis-baut',
//         'content' => 'Dies ist eine umfassende Anleitung...',
//         'author_id' => 1,
//         'author_type' => $this->createTestUser()::class
//     ]);

//     // Update draft
//     $draft->update([
//         'status' => 'published',
//         'data' => ['category' => 'technology', 'featured' => true]
//     ]);

//     // Soft delete
//     $draft->delete();

//     // Restore
//     $draft->restore();

//     // Verify everything works
//     expect($draft->status)->toBe('published');
//     expect($draft->data)->toBe(['category' => 'technology', 'featured' => true]);
//     expect($draft->media)->toHaveCount(1);
//     expect($draft->hasTranslation('en'))->toBeTrue();
//     expect($draft->hasTranslation('de'))->toBeTrue();
//     expect($draft->trashed())->toBeFalse();
// });
