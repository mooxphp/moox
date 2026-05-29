<?php

declare(strict_types=1);

namespace Moox\Tag\Database\Seeders;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Moox\Demo\Seeding\FormatsFakerLocaleText;
use Moox\Demo\Seeding\LoadsImageMediaPool;
use Moox\Demo\Seeding\ReportsMooxSeederProgress;
use Moox\Demo\Seeding\RunsMooxDemoAssets;
use Moox\Demo\Seeding\SeedingConfig;
use Moox\Demo\Seeding\SeedOutput;
use Moox\Media\Models\Media;
use Moox\Tag\Models\Tag;

class TagSeeder extends Seeder
{
    use FormatsFakerLocaleText;
    use LoadsImageMediaPool;
    use ReportsMooxSeederProgress;

    public const DEMO_SLUG_PREFIX = 'demo-tag';

    public const DEFAULT_TAG_COUNT = 100;

    /** @var list<string> */
    public const LOCALES = ['cs_CZ', 'en_US', 'de_DE', 'pl_PL'];

    /** @var list<string> */
    private const TAG_STATUSES = ['draft', 'waiting', 'private', 'scheduled', 'published'];

    private const MEDIA_ATTACH_PROBABILITY = 0.8;

    private const PROGRESS_LOG_EVERY = 100;

    public function run(): void
    {
        $this->seed();

        if (class_exists(RunsMooxDemoAssets::class)) {
            RunsMooxDemoAssets::invoke($this);
        }
    }

    protected function seed(): void
    {
        if (! $this->assertRequiredLocalizations(self::LOCALES)) {
            return;
        }

        $this->purgeDemoTags();

        $author = $this->requireDemoAuthor();
        if ($author === null) {
            return;
        }

        $faker = fake();
        $count = $this->resolveTagCount();
        $baseUrl = rtrim((string) config('app.url'), '/');
        $mediaPool = $this->loadImageMediaPool();
        $created = 0;
        $withMedia = 0;

        if ($mediaPool->isEmpty()) {
            $this->command->warn('No images in media table - tags will be seeded without media_usables.');
        }

        $progress = $this->hasSeedOutput()
            ? SeedOutput::progressBar($count, 'Demo tags')
            : null;

        DB::transaction(function () use ($count, $faker, $author, $baseUrl, $mediaPool, $progress, &$created, &$withMedia): void {
            for ($index = 1; $index <= $count; $index++) {
                $status = $faker->randomElement(self::TAG_STATUSES);

                $tag = Tag::query()->create([
                    'is_active' => $faker->boolean(85),
                    'color' => $faker->hexColor(),
                    'weight' => $faker->numberBetween(1, 10),
                    'count' => $faker->numberBetween(0, 100),
                    'status' => $status,
                    'due_at' => $faker->optional(0.25)->dateTimeBetween('now', '+60 days'),
                    'custom_properties' => [
                        'seed_source' => 'tag_seeder_v1',
                        'seed_index' => $index,
                    ],
                ]);

                foreach (self::LOCALES as $locale) {
                    $localeFaker = $this->fakerForLocale($locale);
                    $title = $this->formatFakerWords($locale, $localeFaker, 1, 3);
                    $slug = self::DEMO_SLUG_PREFIX
                        .'-'.Str::slug($title)
                        .'-'.Str::lower($locale)
                        .'-'.sprintf('%04d', $index);

                    $translation = $tag->translateOrNew($locale);
                    $translation->title = $title;
                    $translation->slug = Str::limit($slug, 180, '');
                    $translation->permalink = $baseUrl.'/'.$locale.'/'.$translation->slug;
                    $translation->description = $this->fakerLocaleText($locale, $localeFaker, preset: 'description');
                    $translation->content = implode("\n\n", $this->fakerLocaleParagraphs(
                        $locale,
                        $localeFaker,
                        2,
                        4,
                        120,
                        280,
                    ));
                    $translation->translation_status = $status;

                    $this->assignTranslationAuthor($translation, $author);
                }

                $tag->save();

                if ($mediaPool->isNotEmpty() && $faker->boolean((int) (self::MEDIA_ATTACH_PROBABILITY * 100))) {
                    /** @var Media $media */
                    $media = $mediaPool->random();

                    DB::table('media_usables')->insertOrIgnore([
                        'media_id' => $media->getKey(),
                        'media_usable_id' => $tag->getKey(),
                        'media_usable_type' => Tag::class,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $withMedia++;
                }

                $created++;

                if ($progress !== null) {
                    $progress->advance();
                } elseif ($index % self::PROGRESS_LOG_EVERY === 0 || $index === $count) {
                    $this->reportCreated("Tag {$tag->getKey()}");
                }
            }
        });

        $progress?->finish("{$count} demo tag(s)");

        $this->reportDetail(sprintf(
            '%d faker tag(s) seeded with %d locale(s) each, %d with media link(s).',
            $created,
            count(self::LOCALES),
            $withMedia
        ));
    }

    private function purgeDemoTags(): void
    {
        Tag::query()
            ->whereHas('translations', function ($query): void {
                $query->where('slug', 'like', self::DEMO_SLUG_PREFIX.'-%');
            })
            ->forceDelete();
    }

    private function resolveTagCount(): int
    {
        if (class_exists(SeedingConfig::class)) {
            return SeedingConfig::resolveCount('tag', self::DEFAULT_TAG_COUNT);
        }

        return self::DEFAULT_TAG_COUNT;
    }

    private function fakerForLocale(string $locale): Generator
    {
        static $cache = [];
        $resolvedLocale = in_array($locale, self::LOCALES, true) ? $locale : 'en_US';

        if (! isset($cache[$resolvedLocale])) {
            $cache[$resolvedLocale] = FakerFactory::create($resolvedLocale);
        }

        return $cache[$resolvedLocale];
    }
}
