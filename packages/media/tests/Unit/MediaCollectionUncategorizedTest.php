<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
});

afterEach(function (): void {
    Schema::dropIfExists('media_collection_translations');
    Schema::dropIfExists('media_collections');
});

it('detects uncategorized collections by translated name', function (): void {
    $collection = MediaCollection::query()->create([]);
    $collection->translateOrNew('en_US')->fill([
        'name' => 'Uncategorized',
        'description' => 'Default',
    ]);
    $collection->save();
    $collection->load('translations');

    expect($collection->isUncategorized())->toBeTrue();
});

it('does not treat named collections as uncategorized', function (): void {
    $collection = MediaCollection::query()->create([]);
    $collection->translateOrNew('en_US')->fill([
        'name' => 'Marketing',
        'description' => 'Campaign assets',
    ]);
    $collection->save();
    $collection->load('translations');

    expect($collection->isUncategorized())->toBeFalse();
});

it('creates an uncategorized collection when none exists', function (): void {
    config(['app.locale' => 'en']);

    $collection = MediaCollection::resolveUncategorized();

    expect($collection->exists)->toBeTrue()
        ->and($collection->isUncategorized())->toBeTrue()
        ->and(MediaCollection::query()->count())->toBe(1);
});
