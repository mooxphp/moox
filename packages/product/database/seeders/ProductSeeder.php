<?php

declare(strict_types=1);

namespace Moox\Product\Database\Seeders;

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
use Moox\Product\Models\Product;
class ProductSeeder extends Seeder
{
    use FormatsFakerLocaleText;
    use LoadsImageMediaPool;
    use ReportsMooxSeederProgress;

    public const DEMO_SLUG_PREFIX = 'demo-product';

    public const DEFAULT_PRODUCT_COUNT = 100;

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
    private const PRODUCT_TYPES = ['simple', 'configurable', 'bundle', 'digital'];

    /** @var list<string> */
    private const PRODUCT_STATUSES = ['draft', 'waiting', 'private', 'scheduled', 'published'];

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
        if (! $this->assertRequiredLocalizations(self::LOCALES)) {
            return;
        }

        $this->purgeDemoProducts();

        $author = $this->requireDemoAuthor();
        if ($author === null) {
            return;
        }

        $faker = fake();
        $count = $this->resolveProductCount();
        $baseUrl = rtrim((string) config('app.url'), '/');
        $mediaPool = $this->loadImageMediaPool();

        if ($mediaPool->isEmpty()) {
            $this->command?->warn('No images in `media` table — products will be seeded without mediathek images.');
        }

        $created = 0;
        $progress = $this->hasSeedOutput()
            ? SeedOutput::progressBar($count, 'Demo products')
            : null;

        DB::transaction(function () use ($count, $faker, $author, $baseUrl, $mediaPool, $progress, &$created): void {
            for ($index = 1; $index <= $count; $index++) {
                $status = $faker->randomElement(self::PRODUCT_STATUSES);
                $sku = 'DEMO-'.strtoupper($faker->unique()->bothify('??###??'));
                $imageLocale = self::LOCALES[array_rand(self::LOCALES)];

                $product = Product::query()->create([
                    'is_active' => $faker->boolean(88),
                    'image' => $this->resolveProductImage($faker, $mediaPool, $imageLocale),
                    'type' => $faker->randomElement(self::PRODUCT_TYPES),
                    'due_at' => $faker->optional(0.3)->dateTimeBetween('now', '+90 days'),
                    'color' => $faker->hexColor(),
                    'sku' => $sku,
                    'gtin' => $faker->optional(0.6)->numerify('##############'),
                    'mpn' => $faker->optional(0.7)->bothify('MPN-####-??'),
                    'brand_name' => null,
                    'weight_grams' => $faker->numberBetween(100, 25000),
                    'length_mm' => $faker->numberBetween(50, 1500),
                    'width_mm' => $faker->numberBetween(50, 1200),
                    'height_mm' => $faker->numberBetween(20, 1000),
                    'status' => $status,
                    'custom_properties' => [
                        'seed_source' => 'product_seeder_v1',
                        'seed_index' => $index,
                        'featured' => $faker->boolean(20),
                    ],
                ]);

                foreach (self::LOCALES as $locale) {
                    $localeFaker = $this->fakerForLocale($locale);
                    $title = $this->formatFakerWords($locale, $localeFaker, 2, 5);
                    $slug = self::DEMO_SLUG_PREFIX
                        .'-'.Str::slug($title)
                        .'-'.Str::lower($locale)
                        .'-'.sprintf('%04d', $index);

                    $translation = $product->translateOrNew($locale);
                    $translation->title = $title;
                    $translation->slug = Str::limit($slug, 180, '');
                    $translation->permalink = $baseUrl.'/'.$locale.'/products/'.$translation->slug;
                    $translation->subtitle = $this->fakerLocaleSentence($locale, $localeFaker, 40, 100);
                    $translation->excerpt = $this->fakerLocaleText($locale, $localeFaker, 80, 180, limit: 180);
                    $translation->description = $this->fakerLocaleText($locale, $localeFaker, preset: 'description');
                    $translation->content = implode("\n\n", $this->fakerLocaleParagraphs(
                        $locale,
                        $localeFaker,
                        2,
                        5,
                        120,
                        280,
                    ));
                    $translation->meta_title = $title.' - '.$localeFaker->company();
                    $translation->meta_description = Str::limit($translation->description ?? '', 140, '');
                    $translation->translation_status = $status;

                    $this->assignTranslationAuthor($translation, $author);
                }

                $product->save();
                $created++;

                if ($progress !== null) {
                    $progress->advance();
                } elseif ($index % self::PROGRESS_LOG_EVERY === 0 || $index === $count) {
                    $this->reportCreated("Product {$product->getKey()}");
                }
            }
        });

        $progress?->finish("{$count} demo product(s)");

        $this->reportDetail(sprintf(
            '%d faker product(s) seeded with %d locale(s) each (Locale-Lock per translation).',
            $created,
            count(self::LOCALES)
        ));
    }

    /**
     * @param  Collection<int, \Moox\Media\Models\Media>  $mediaPool
     * @return array<string, mixed>|null
     */
    private function resolveProductImage(\Faker\Generator $faker, Collection $mediaPool, string $locale): ?array
    {
        if ($mediaPool->isNotEmpty() && $faker->boolean((int) (self::MEDIA_ATTACH_PROBABILITY * 100))) {
            return $this->randomImageFieldFromPool($mediaPool, $locale);
        }

        return null;
    }

    private function purgeDemoProducts(): void
    {
        Product::query()
            ->whereHas('translations', function ($query): void {
                $query->where('slug', 'like', self::DEMO_SLUG_PREFIX.'-%');
            })
            ->forceDelete();
    }

    private function resolveProductCount(): int
    {
        if (class_exists(SeedingConfig::class)) {
            return SeedingConfig::resolveCount('product', self::DEFAULT_PRODUCT_COUNT);
        }

        return self::DEFAULT_PRODUCT_COUNT;
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
