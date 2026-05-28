<?php

declare(strict_types=1);

namespace Moox\Record\Database\Seeders;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Moox\Record\Enums\RecordStatus;
use Moox\Record\Models\Record;
use Moox\User\Models\User;

class RecordSeeder extends Seeder
{
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

        if (class_exists(\Moox\Demo\Seeding\RunsMooxDemoAssets::class)) {
            \Moox\Demo\Seeding\RunsMooxDemoAssets::invoke($this);
        }
    }

    protected function seed(): void
    {
        $this->purgeDemoRecords();

        $count = $this->resolveRecordCount();
        $author = User::query()->first();
        $faker = fake();
        $created = 0;

        DB::transaction(function () use ($count, $author, $faker, &$created): void {
            for ($index = 1; $index <= $count; $index++) {
                $locale = self::LOCALES[array_rand(self::LOCALES)];
                $title = $this->localizedTitle($locale);
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
                    'description' => $this->localizedDescription($locale),
                    'permalink' => rtrim((string) config('app.url'), '/').'/'.$locale.'/'.$slug,
                    'status' => $status,
                    'custom_properties' => [
                        'seed_source' => 'record_seeder_v1',
                        'seed_index' => $index,
                        'seed_locale' => $locale,
                    ],
                    'author_id' => $author?->getKey(),
                    'author_type' => $author?->getMorphClass(),
                ]);

                $created++;
                if ($index % self::PROGRESS_LOG_EVERY === 0 || $index === $count) {
                    $this->reportCreated("Record {$record->getKey()}");
                }
            }
        });

        $this->reportDetail(sprintf(
            '%d faker record(s) seeded across %d locale(s).',
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

    private function resolveRecordCount(): int
    {
        if (class_exists(\Moox\Demo\Seeding\SeedingConfig::class)) {
            return \Moox\Demo\Seeding\SeedingConfig::resolveCount('record', self::DEFAULT_RECORD_COUNT);
        }

        return self::DEFAULT_RECORD_COUNT;
    }

    private function localizedTitle(string $locale): string
    {
        return Str::title($this->fakerForLocale($locale)->words(random_int(2, 6), true));
    }

    private function localizedDescription(string $locale): string
    {
        return $this->fakerForLocale($locale)->paragraph(2);
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
