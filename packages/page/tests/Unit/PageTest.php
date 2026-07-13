<?php

use Carbon\Carbon;
use Moox\Page\Models\Page;

beforeEach(function () {
    $this->user = $this->createTestUser();
    $this->actingAs($this->user);
});

test('draft can be created with real data in different languages', function () {
    $user = $this->user;
    $page = Page::create([
        'is_active' => true,
        'image' => ['url' => 'https://example.com/image.jpg', 'alt' => 'Test image'],
        'type' => 'article',
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

    $page->createTranslation('de', [
        'title' => 'Deutscher Titel',
        'slug' => 'deutscher-titel',
        'permalink' => 'https://example.com/permalink-de',
        'description' => 'Deutsche Beschreibung',
        'content' => 'Deutscher Inhalt hier',
        'author_id' => 1,
        'author_type' => $user::class,
    ]);
    $page->createTranslation('es', [
        'title' => 'Español Titel',
        'slug' => 'español-titel',
        'permalink' => 'https://example.com/permalink-es',
        'description' => 'Español Beschreibung',
        'content' => 'Español Inhalt hier',
        'author_id' => 1,
        'author_type' => $user::class,
    ]);

    expect($page)->toBeInstanceOf(Page::class);
    expect($page->is_active)->toBeTrue();
    expect($page->type)->toBe('article');
    expect($page->due_at)->toBeInstanceOf(Carbon::class);
    expect($page->status)->toBe('draft');
    expect($page->custom_properties)->toBe(['theme' => 'dark', 'layout' => 'grid']);
    expect($page->image)->toBe(['url' => 'https://example.com/image.jpg', 'alt' => 'Test image']);
    expect($page->permalink)->toBe('https://example.com/permalink');
    expect($page->title)->toBe('English Title');
    expect($page->slug)->toBe('english-title');
    expect($page->description)->toBe('English description');
    expect($page->content)->toBe('English content here');
    expect($page->author_id)->toBe(1);
    expect($page->author_type)->toBe($user::class);
    expect($page->hasTranslation('en'))->toBeTrue();
    expect($page->hasTranslation('de'))->toBeTrue();
    expect($page->hasTranslation('es'))->toBeTrue();
    expect($page->getAvailableTranslations())->toContain('en', 'de', 'es');
    expect($page->translate('en')->title)->toBe('English Title');
    expect($page->translate('de')->title)->toBe('Deutscher Titel');
    expect($page->translate('es')->title)->toBe('Español Titel');
    expect($page->translate('en')->slug)->toBe('english-title');
    expect($page->translate('de')->slug)->toBe('deutscher-titel');
    expect($page->translate('es')->slug)->toBe('español-titel');
    expect($page->translate('en')->permalink)->toBe('https://example.com/permalink');
    expect($page->translate('de')->permalink)->toBe('https://example.com/permalink-de');
    expect($page->translate('es')->permalink)->toBe('https://example.com/permalink-es');
    expect($page->translate('en')->description)->toBe('English description');
    expect($page->translate('de')->description)->toBe('Deutsche Beschreibung');
    expect($page->translate('es')->description)->toBe('Español Beschreibung');
    expect($page->translate('en')->content)->toBe('English content here');
    expect($page->translate('de')->content)->toBe('Deutscher Inhalt hier');
    expect($page->translate('es')->content)->toBe('Español Inhalt hier');
    expect($page->translate('en')->author_id)->toBe(1);
    expect($page->translate('de')->author_id)->toBe(1);
    expect($page->translate('es')->author_id)->toBe(1);
    expect($page->translate('en')->author_type)->toBe($user::class);
    expect($page->translate('de')->author_type)->toBe($user::class);
    expect($page->translate('es')->author_type)->toBe($user::class);
});

test('draft can be updated', function () {
    $page = Page::factory()->create([
        'type' => 'article',
        'status' => 'draft',
    ]);

    $page->update([
        'type' => 'page',
        'status' => 'published',
    ]);

    $page->refresh();

    expect($page->type)->toBe('page');
    expect($page->status)->toBe('published');
});

test('draft translations can be soft deleted', function () {
    $page = Page::factory()->create();

    expect($page->getTranslationsArray())->toHaveCount(4);

    $page->deleteTranslations('de');
    expect($page->getTranslationsArray())->toHaveCount(3);
    expect($page->hasTranslation('de'))->toBeFalse();

    $page->deleteTranslations('es');
    expect($page->getTranslationsArray())->toHaveCount(2);
    expect($page->hasTranslation('es'))->toBeFalse();

    $page->deleteTranslations('fr');
    expect($page->getTranslationsArray())->toHaveCount(1);
    expect($page->hasTranslation('fr'))->toBeFalse();

    $page->deleteTranslations('en');
    expect($page->getTranslationsArray())->toHaveCount(0);
    expect($page->hasTranslation('en'))->toBeFalse();

    $page->checkAndDeleteIfAllTranslationsDeleted();
    expect($page->trashed())->toBeTrue();
});
