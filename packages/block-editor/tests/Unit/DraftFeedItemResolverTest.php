<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Moox\BlockEditor\EntityQuery\Mapping\DraftFeedItemResolver;
use Moox\BlockEditor\EntityQuery\Mapping\FeedItemMapping;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Schema::dropIfExists('feed_item_translation_models');
    Schema::dropIfExists('feed_item_models');

    Schema::create('feed_item_models', function (Blueprint $table): void {
        $table->id();
        $table->boolean('is_active')->default(true);
        $table->json('image')->nullable();
        $table->string('type')->nullable();
    });

    Schema::create('feed_item_translation_models', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('feed_item_model_id');
        $table->string('locale');
        $table->string('title')->nullable();
        $table->string('slug')->nullable();
        $table->string('permalink')->nullable();
        $table->text('description')->nullable();
        $table->text('excerpt')->nullable();
        $table->timestamp('published_at')->nullable();
        $table->string('translation_status')->default('published');
        $table->timestamp('deleted_at')->nullable();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('feed_item_translation_models');
    Schema::dropIfExists('feed_item_models');
});

it('maps draft models into the canonical feed item structure', function (): void {
    $model = FeedItemTestModel::query()->create([
        'is_active' => true,
        'image' => ['id' => 99],
        'type' => 'article',
    ]);

    $model->translations()->create([
        'locale' => 'de_DE',
        'title' => 'Erster Beitrag',
        'slug' => 'erster-beitrag',
        'permalink' => '/news/erster-beitrag',
        'description' => '<p>Beschreibung</p>',
        'excerpt' => '<p>Teaser</p>',
        'published_at' => now(),
        'translation_status' => 'published',
    ]);

    $model->load('translations');

    $resolver = app(DraftFeedItemResolver::class);
    $mapping = FeedItemMapping::fromConfig([
        'taxonomy' => null,
        'extra' => [
            'type' => 'model:type',
        ],
    ]);

    $resolver->prepare(Collection::make([$model]), $mapping);

    $item = $resolver->resolve($model, 'de', $mapping)?->toArray();

    expect($item)
        ->toMatchArray([
            'id' => $model->getKey(),
            'title' => 'Erster Beitrag',
            'slug' => 'erster-beitrag',
            'permalink' => '/news/erster-beitrag',
            'description_plain' => 'Beschreibung',
            'excerpt_plain' => 'Teaser',
            'author_name' => null,
            'categories' => [],
            'type' => 'article',
        ])
        ->and($item['published_at'])->not->toBeNull();
});

it('falls back to excerpt and uses the configured untitled label', function (): void {
    $model = FeedItemTestModel::query()->create([
        'is_active' => true,
        'image' => [],
    ]);

    $model->translations()->create([
        'locale' => 'en_US',
        'title' => '',
        'slug' => 'fallback-slug',
        'permalink' => '/news/fallback',
        'description' => '',
        'excerpt' => '<p>Fallback teaser</p>',
        'translation_status' => 'published',
    ]);

    $model->load('translations');

    $item = app(DraftFeedItemResolver::class)->resolve(
        $model,
        'en',
        FeedItemMapping::fromConfig([
            'taxonomy' => null,
            'untitled_label' => 'No title',
        ]),
    )?->toArray();

    expect($item)
        ->not->toBeNull()
        ->and($item['title'])->toBe('Fallback teaser');
});

final class FeedItemTestModel extends Model
{
    protected $table = 'feed_item_models';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'image' => 'array',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(FeedItemTestTranslationModel::class, 'feed_item_model_id');
    }

    public function translate(?string $locale = null, bool $withFallback = false): ?FeedItemTestTranslationModel
    {
        unset($withFallback);

        if ($locale === null) {
            return $this->translations->first();
        }

        return $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->first();
    }
}

final class FeedItemTestTranslationModel extends Model
{
    protected $table = 'feed_item_translation_models';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
