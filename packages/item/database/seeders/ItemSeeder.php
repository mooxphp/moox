<?php

declare(strict_types=1);

namespace Moox\Item\Database\Seeders;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Moox\Demo\Seeding\RunsMooxDemoAssets;
use Moox\Demo\Seeding\SeedingConfig;
use Moox\Demo\Seeding\SeedOutput;
use Moox\Item\Models\Item;

class ItemSeeder extends Seeder
{
    public const DEMO_TITLE_PREFIX = 'Demo Item';

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
        $this->purgeDemoItems();

        $faker = fake();
        $count = $this->resolveItemCount();
        $created = 0;

        DB::transaction(function () use ($count, $faker, &$created): void {
            for ($index = 1; $index <= $count; $index++) {
                $locale = self::LOCALES[array_rand(self::LOCALES)];
                $title = $this->localizedTitle($locale);

                $item = Item::query()->create([
                    'title' => $title,
                    'description' => $this->localizedDescription($locale),
                    'custom_properties' => [
                        'seed_source' => 'item_seeder_v1',
                        'seed_index' => $index,
                        'seed_locale' => $locale,
                        'seed_key' => Str::slug($title).'-'.sprintf('%04d', $index),
                        'is_featured' => $faker->boolean(25),
                    ],
                ]);

                $created++;
                if ($index % self::PROGRESS_LOG_EVERY === 0 || $index === $count) {
                    $this->reportCreated("Item {$item->getKey()}");
                }
            }
        });

        $this->reportDetail(sprintf(
            '%d faker item(s) seeded across %d locale(s).',
            $created,
            count(self::LOCALES)
        ));
    }

    private function purgeDemoItems(): void
    {
        Item::query()
            ->where('custom_properties->seed_source', 'item_seeder_v1')
            ->orWhere('title', 'like', self::DEMO_TITLE_PREFIX.'%')
            ->delete();
    }

    private function reportCreated(string $label): void
    {
        if ($this->hasSeedOutput()) {
            SeedOutput::created($label);

            return;
        }
    }

    private function reportDetail(string $line): void
    {
        if ($this->hasSeedOutput()) {
            SeedOutput::detail($line);

            return;
        }

        $this->command?->info($line);
    }

    private function hasSeedOutput(): bool
    {
        return class_exists(SeedOutput::class)
            && SeedOutput::isBound();
    }

    private function resolveItemCount(): int
    {
        if (class_exists(SeedingConfig::class)) {
            return SeedingConfig::resolveCount('item', self::DEFAULT_ITEM_COUNT);
        }

        return self::DEFAULT_ITEM_COUNT;
    }

    private function localizedTitle(string $locale): string
    {
        return self::DEMO_TITLE_PREFIX.' '.Str::title($this->fakerForLocale($locale)->words(random_int(2, 5), true));
    }

    private function localizedDescription(string $locale): string
    {
        return $this->fakerForLocale($locale)->paragraph();
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
