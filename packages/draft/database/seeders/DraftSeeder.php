<?php

declare(strict_types=1);

namespace Moox\Draft\Database\Seeders;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Moox\Draft\Models\Draft;
use Moox\User\Models\User;

class DraftSeeder extends Seeder
{
    public const DEMO_SLUG_PREFIX = 'demo-draft';

    public const DEFAULT_DRAFT_COUNT = 100;

    /** @var list<string> */
    public const LOCALES = ['cs_CZ', 'en_US', 'de_DE', 'pl_PL'];

    /** @var array<string, string> */
    private const FAKER_LOCALE_MAP = [
        'cs_CZ' => 'cs_CZ',
        'en_US' => 'en_US',
        'de_DE' => 'de_DE',
        'pl_PL' => 'pl_PL',
    ];

    /** @var list<string> */
    private const TYPES = ['article', 'page', 'post', 'news', 'tutorial'];

    /** @var list<string> */
    private const TRANSLATION_STATUSES = ['draft', 'waiting', 'private', 'scheduled', 'published'];
    private const PROGRESS_LOG_EVERY = 100;

    public function run(): void
    {
        $this->seed();

        if (class_exists(\Moox\Demo\Seeding\RunsMooxDemoAssets::class)) {
            \Moox\Demo\Seeding\RunsMooxDemoAssets::invoke($this);
        }
    }

    protected function seed(): void
    {
        $this->purgeDemoDrafts();

        $count = $this->resolveDraftCount();
        $author = User::query()->first();
        $created = 0;
        $faker = fake();

        DB::transaction(function () use ($count, $faker, $author, &$created): void {
            for ($index = 1; $index <= $count; $index++) {
                $status = $faker->randomElement(self::TRANSLATION_STATUSES);
                $draft = Draft::query()->create([
                    'is_active' => $faker->boolean(85),
                    'type' => $faker->randomElement(self::TYPES),
                    'color' => $faker->hexColor(),
                    'status' => $status,
                    'due_at' => $faker->optional(0.4)->dateTimeBetween('now', '+45 days'),
                    'image' => [
                        'url' => $faker->imageUrl(1200, 630),
                        'alt' => $faker->sentence(4),
                    ],
                    'data' => json_encode([
                        'seed_source' => 'draft_seeder_v1',
                        'seed_index' => $index,
                    ], JSON_THROW_ON_ERROR),
                ]);

                foreach (self::LOCALES as $locale) {
                    $title = $this->localizedTitle($locale);
                    $slug = self::DEMO_SLUG_PREFIX
                        .'-'.Str::slug($title)
                        .'-'.Str::lower($locale)
                        .'-'.sprintf('%04d', $index);

                    $translation = $draft->translateOrNew($locale);
                    $translation->title = $title;
                    $translation->slug = Str::limit($slug, 180, '');
                    $translation->permalink = rtrim((string) config('app.url'), '/').'/'.$locale.'/'.$translation->slug;
                    $translation->description = $this->localizedDescription($locale);
                    $translation->content = $this->localizedContent($locale);
                    $translation->translation_status = $status;

                    if ($author !== null) {
                        $translation->author_id = $author->getKey();
                        $translation->author_type = $author->getMorphClass();
                    }
                }

                $draft->save();
                $created++;

                if ($index % self::PROGRESS_LOG_EVERY === 0 || $index === $count) {
                    $this->reportCreated("Draft {$draft->getKey()}");
                }
            }
        });

        $this->reportDetail(sprintf(
            '%d faker draft(s) seeded across %d locale(s).',
            $created,
            count(self::LOCALES)
        ));
    }

    private function purgeDemoDrafts(): void
    {
        Draft::query()
            ->whereHas('translations', function ($query): void {
                $query->where('slug', 'like', self::DEMO_SLUG_PREFIX.'-%');
            })
            ->forceDelete();
    }

    private function reportCreated(string $label): void
    {
        if ($this->hasSeedOutput()) {
            \Moox\Demo\Seeding\SeedOutput::created($label);

            return;
        }
    }

    private function reportDetail(string $line): void
    {
        if ($this->hasSeedOutput()) {
            \Moox\Demo\Seeding\SeedOutput::detail($line);

            return;
        }

        $this->command?->info($line);
    }

    private function hasSeedOutput(): bool
    {
        return class_exists(\Moox\Demo\Seeding\SeedOutput::class)
            && \Moox\Demo\Seeding\SeedOutput::isBound();
    }

    private function resolveDraftCount(): int
    {
        if (class_exists(\Moox\Demo\Seeding\SeedingConfig::class)) {
            return \Moox\Demo\Seeding\SeedingConfig::resolveCount('draft', self::DEFAULT_DRAFT_COUNT);
        }

        return self::DEFAULT_DRAFT_COUNT;
    }

    private function localizedTitle(string $locale): string
    {
        return Str::title($this->fakerForLocale($locale)->words(random_int(3, 7), true));
    }

    private function localizedDescription(string $locale): string
    {
        return $this->fakerForLocale($locale)->paragraph(2);
    }

    private function localizedContent(string $locale): string
    {
        return $this->fakerForLocale($locale)->paragraphs(random_int(3, 6), true);
    }

    private function fakerForLocale(string $locale): Generator
    {
        static $cache = [];
        $resolvedLocale = self::FAKER_LOCALE_MAP[$locale] ?? 'en_US';

        if (! isset($cache[$resolvedLocale])) {
            $cache[$resolvedLocale] = FakerFactory::create($resolvedLocale);
        }

        return $cache[$resolvedLocale];
    }
}
