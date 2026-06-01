<?php

declare(strict_types=1);

namespace Moox\Draft\Database\Seeders;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Moox\Demo\Seeding\FormatsFakerLocaleText;
use Moox\Demo\Seeding\LoadsImageMediaPool;
use Moox\Demo\Seeding\ReportsMooxSeederProgress;
use Moox\Demo\Seeding\RunsMooxDemoAssets;
use Moox\Demo\Seeding\SeedingConfig;
use Moox\Demo\Seeding\SeedOutput;
use Moox\Draft\Models\Draft;

class DraftSeeder extends Seeder
{
    use FormatsFakerLocaleText;
    use LoadsImageMediaPool;
    use ReportsMooxSeederProgress;

    public const DEMO_SLUG_PREFIX = 'demo-draft';

    public const DEFAULT_DRAFT_COUNT = 100;

    /** Fallback when moox/demo is not installed; otherwise {@see locales()}. */
    public const LOCALES = ['cs_CZ', 'en_US', 'de_DE', 'pl_PL'];

    /** @var list<string> */
    private const TYPES = ['article', 'page', 'post', 'news', 'tutorial'];

    /** @var list<string> */
    private const TRANSLATION_STATUSES = ['draft', 'waiting', 'private', 'scheduled', 'published'];

    private const MEDIA_ATTACH_PROBABILITY = 0.75;

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
        if (! $this->assertRequiredLocalizations($this->locales())) {
            return;
        }

        $this->purgeDemoDrafts();

        $author = $this->requireDemoAuthor();
        if ($author === null) {
            return;
        }

        $count = $this->resolveDraftCount();
        $faker = fake();
        $baseUrl = rtrim((string) config('app.url'), '/');
        $mediaPool = $this->loadImageMediaPool();
        $created = 0;

        if ($mediaPool->isEmpty()) {
            $this->command->warn('No images in `media` table — drafts will be seeded without mediathek images.');
        }

        $progress = $this->hasSeedOutput()
            ? SeedOutput::progressBar($count, 'Demo drafts')
            : null;

        DB::transaction(function () use ($count, $faker, $author, $baseUrl, $mediaPool, $progress, &$created): void {
            for ($index = 1; $index <= $count; $index++) {
                $status = $faker->randomElement(self::TRANSLATION_STATUSES);
                $contentLocale = $this->locales()[array_rand($this->locales())];
                $image = $this->resolveDraftImage($faker, $mediaPool, $contentLocale);

                $draft = Draft::query()->create([
                    'is_active' => $faker->boolean(85),
                    'type' => $faker->randomElement(self::TYPES),
                    'color' => $faker->hexColor(),
                    'status' => $status,
                    'due_at' => $faker->optional(0.4)->dateTimeBetween('now', '+45 days'),
                    'image' => $image,
                    'data' => json_encode([
                        'seed_source' => 'draft_seeder_v1',
                        'seed_index' => $index,
                    ], JSON_THROW_ON_ERROR),
                ]);

                foreach ($this->locales() as $locale) {
                    $localeFaker = $this->fakerForLocale($locale);
                    $title = $this->formatFakerWords($locale, $localeFaker, 3, 7);
                    $slug = self::DEMO_SLUG_PREFIX
                        .'-'.Str::slug($title)
                        .'-'.Str::lower($locale)
                        .'-'.sprintf('%04d', $index);

                    $translation = $draft->translateOrNew($locale);
                    $translation->title = $title;
                    $translation->slug = Str::limit($slug, 180, '');
                    $translation->permalink = $baseUrl.'/'.$locale.'/'.$translation->slug;
                    $translation->description = $this->fakerLocaleText($locale, $localeFaker, preset: 'description');
                    $translation->content = implode("\n\n", $this->fakerLocaleParagraphs(
                        $locale,
                        $localeFaker,
                        3,
                        6,
                        120,
                        280,
                    ));
                    $translation->translation_status = $status;

                    $this->assignTranslationAuthor($translation, $author);
                }

                $draft->save();
                $created++;

                if ($progress !== null) {
                    $progress->advance();
                } elseif ($index % self::PROGRESS_LOG_EVERY === 0 || $index === $count) {
                    $this->reportCreated("Draft {$draft->getKey()}");
                }
            }
        });

        $progress?->finish("{$count} demo draft(s)");

        $this->reportDetail(sprintf(
            '%d faker draft(s) seeded with %d locale(s) each.',
            $created,
            count($this->locales())
        ));
    }

    /**
     * @return array{media_id: int, locale: string}|null
     */
    private function resolveDraftImage(Generator $faker, Collection $mediaPool, string $locale): ?array
    {
        if ($mediaPool->isNotEmpty() && $faker->boolean((int) (self::MEDIA_ATTACH_PROBABILITY * 100))) {
            return $this->randomImageFieldFromPool($mediaPool, $locale);
        }

        return null;
    }

    private function purgeDemoDrafts(): void
    {
        Draft::query()
            ->whereHas('translations', function ($query): void {
                $query->where('slug', 'like', self::DEMO_SLUG_PREFIX.'-%');
            })
            ->forceDelete();
    }

    private function resolveDraftCount(): int
    {
        if (class_exists(SeedingConfig::class)) {
            return SeedingConfig::resolveCount('draft', self::DEFAULT_DRAFT_COUNT);
        }

        return self::DEFAULT_DRAFT_COUNT;
    }

    private function fakerForLocale(string $locale): Generator
    {
        static $cache = [];
        $resolvedLocale = in_array($locale, $this->locales(), true) ? $locale : 'en_US';

        if (! isset($cache[$resolvedLocale])) {
            $cache[$resolvedLocale] = FakerFactory::create($resolvedLocale);
        }

        return $cache[$resolvedLocale];
    }
}
