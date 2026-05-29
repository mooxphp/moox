<?php

declare(strict_types=1);

namespace Moox\Item\Database\Seeders;

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
use Moox\Item\Models\Item;

class ItemSeeder extends Seeder
{
    use FormatsFakerLocaleText;
    use ReportsMooxSeederProgress;

    public const DEFAULT_ITEM_COUNT = 100;

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

        $this->purgeDemoItems();

        $faker = fake();
        $count = $this->resolveItemCount();
        $created = 0;

        $progress = $this->hasSeedOutput()
            ? SeedOutput::progressBar($count, 'Demo items')
            : null;

        DB::transaction(function () use ($count, $faker, $progress, &$created): void {
            for ($index = 1; $index <= $count; $index++) {
                $locale = self::LOCALES[array_rand(self::LOCALES)];
                $localeFaker = $this->fakerForLocale($locale);
                $title = $this->formatFakerWords($locale, $localeFaker, 2, 5);

                $item = Item::query()->create([
                    'title' => $title,
                    'description' => $this->fakerLocaleText($locale, $localeFaker, preset: 'description'),
                    'custom_properties' => [
                        'seed_source' => 'item_seeder_v1',
                        'seed_index' => $index,
                        'seed_locale' => $locale,
                        'seed_key' => Str::slug($title).'-'.sprintf('%04d', $index),
                        'is_featured' => $faker->boolean(25),
                    ],
                ]);

                $created++;

                if ($progress !== null) {
                    $progress->advance();
                } elseif ($index % self::PROGRESS_LOG_EVERY === 0 || $index === $count) {
                    $this->reportCreated("Item {$item->getKey()}");
                }
            }
        });

        $progress?->finish("{$count} demo item(s)");

        $this->reportDetail(sprintf(
            '%d faker item(s) seeded (one random locale per item from %d configured locale(s)).',
            $created,
            count(self::LOCALES)
        ));
    }

    private function purgeDemoItems(): void
    {
        Item::query()
            ->where('custom_properties->seed_source', 'item_seeder_v1')
            ->delete();
    }

    private function resolveItemCount(): int
    {
        if (class_exists(SeedingConfig::class)) {
            return SeedingConfig::resolveCount('item', self::DEFAULT_ITEM_COUNT);
        }

        return self::DEFAULT_ITEM_COUNT;
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
