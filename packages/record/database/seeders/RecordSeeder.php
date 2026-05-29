<?php

declare(strict_types=1);

namespace Moox\Record\Database\Seeders;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Moox\Demo\Seeding\FormatsFakerLocaleText;
use Moox\Demo\Seeding\ReportsMooxSeederProgress;
use Moox\Demo\Seeding\RunsMooxDemoAssets;
use Moox\Demo\Seeding\SeedingConfig;
use Moox\Demo\Seeding\SeedOutput;
use Moox\Record\Enums\RecordStatus;
use Moox\Record\Models\Record;
class RecordSeeder extends Seeder
{
    use FormatsFakerLocaleText;
    use ReportsMooxSeederProgress;

    public const DEMO_SLUG_PREFIX = 'demo-record';

    public const DEFAULT_RECORD_COUNT = 100;

    /** @var list<string> */
    public const LOCALES = ['cs_CZ', 'en_US', 'de_DE', 'pl_PL'];

    /** @var array<string, string> */
    private const FAKER_LOCALE_MAP = [
        'cs_CZ' => 'cs_CZ',
        'en_US' => 'en_US',
        'de_DE' => 'de_DE',
        'pl_PL' => 'pl_PL',
    ];

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

        $this->purgeDemoRecords();

        $author = $this->requireDemoAuthor();
        if ($author === null) {
            return;
        }

        $count = $this->resolveRecordCount();
        $faker = fake();
        $baseUrl = rtrim((string) config('app.url'), '/');
        $created = 0;

        $progress = $this->hasSeedOutput()
            ? SeedOutput::progressBar($count, 'Demo records')
            : null;

        DB::transaction(function () use ($count, $author, $faker, $baseUrl, $progress, &$created): void {
            for ($index = 1; $index <= $count; $index++) {
                $locale = self::LOCALES[array_rand(self::LOCALES)];
                $localeFaker = $this->fakerForLocale($locale);
                $title = $this->formatFakerWords($locale, $localeFaker, 2, 6);
                $slug = self::DEMO_SLUG_PREFIX
                    .'-'.Str::slug($title)
                    .'-'.Str::lower($locale)
                    .'-'.sprintf('%04d', $index);
                $status = $faker->randomElement([
                    RecordStatus::ACTIVE->value,
                    RecordStatus::INACTIVE->value,
                    RecordStatus::ARCHIVED->value,
                ]);

                $record = Record::query()->create([
                    'title' => $title,
                    'slug' => Str::limit($slug, 180, ''),
                    'description' => $this->fakerLocaleText($locale, $localeFaker, 150, 260),
                    'permalink' => $baseUrl.'/'.$locale.'/'.$slug,
                    'status' => $status,
                    'custom_properties' => [
                        'seed_source' => 'record_seeder_v1',
                        'seed_index' => $index,
                        'seed_locale' => $locale,
                    ],
                    'author_id' => $author->getKey(),
                    'author_type' => $author->getMorphClass(),
                ]);

                $created++;

                if ($progress !== null) {
                    $progress->advance();
                } elseif ($index % self::PROGRESS_LOG_EVERY === 0 || $index === $count) {
                    $this->reportCreated("Record {$record->getKey()}");
                }
            }
        });

        $progress?->finish("{$count} demo record(s)");

        $this->reportDetail(sprintf(
            '%d faker record(s) seeded (one random locale per record from %d configured locale(s)).',
            $created,
            count(self::LOCALES)
        ));
    }

    private function purgeDemoRecords(): void
    {
        Record::query()
            ->where('slug', 'like', self::DEMO_SLUG_PREFIX.'-%')
            ->forceDelete();
    }

    private function resolveRecordCount(): int
    {
        if (class_exists(SeedingConfig::class)) {
            return SeedingConfig::resolveCount('record', self::DEFAULT_RECORD_COUNT);
        }

        return self::DEFAULT_RECORD_COUNT;
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
