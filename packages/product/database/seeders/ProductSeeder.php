<?php

declare(strict_types=1);

namespace Moox\Product\Database\Seeders;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Moox\Product\Models\Product;
use Moox\User\Models\User;

class ProductSeeder extends Seeder
{
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

    public function run(): void
    {
        $this->seed();

        if (class_exists(\Moox\Demo\Seeding\RunsMooxDemoAssets::class)) {
            \Moox\Demo\Seeding\RunsMooxDemoAssets::invoke($this);
        }
    }

    protected function seed(): void
    {
        $this->purgeDemoProducts();

        $faker = fake();
        $author = User::query()->first();
        $count = $this->resolveProductCount();
        $created = 0;

        for ($index = 1; $index <= $count; $index++) {
            $status = $faker->randomElement(self::PRODUCT_STATUSES);
            $sku = 'DEMO-'.strtoupper($faker->unique()->bothify('??###??'));

            $product = Product::query()->create([
                'is_active' => $faker->boolean(88),
                'image' => [
                    'url' => $faker->imageUrl(1200, 630),
                    'alt' => $faker->sentence(4),
                ],
                'type' => $faker->randomElement(self::PRODUCT_TYPES),
                'due_at' => $faker->optional(0.3)->dateTimeBetween('now', '+90 days'),
                'color' => $faker->hexColor(),
                'sku' => $sku,
                'gtin' => $faker->optional(0.6)->numerify('##############'),
                'mpn' => $faker->optional(0.7)->bothify('MPN-####-??'),
                'brand_name' => $faker->company(),
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
                $title = $this->localizedTitle($locale);
                $slug = self::DEMO_SLUG_PREFIX
                    .'-'.Str::slug($title)
                    .'-'.Str::lower($locale)
                    .'-'.sprintf('%04d', $index);

                $translation = $product->translateOrNew($locale);
                $translation->title = $title;
                $translation->slug = Str::limit($slug, 180, '');
                $translation->permalink = rtrim((string) config('app.url'), '/').'/'.$locale.'/products/'.$translation->slug;
                $translation->subtitle = $this->localizedSubtitle($locale);
                $translation->excerpt = $this->localizedExcerpt($locale);
                $translation->description = $this->localizedDescription($locale);
                $translation->content = $this->localizedContent($locale);
                $translation->meta_title = $title.' - '.$product->brand_name;
                $translation->meta_description = Str::limit($translation->description ?? '', 140, '');
                $translation->translation_status = $status;

                if ($author !== null) {
                    $translation->author_id = $author->getKey();
                    $translation->author_type = $author->getMorphClass();
                }
            }

            $product->save();
            $created++;
            $this->reportCreated("Product {$product->getKey()}");
        }

        $this->reportDetail(sprintf(
            '%d faker product(s) seeded across %d locale(s).',
            $created,
            count(self::LOCALES)
        ));
    }

    private function purgeDemoProducts(): void
    {
        Product::query()
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

    private function resolveProductCount(): int
    {
        if (class_exists(\Moox\Demo\Seeding\SeedingConfig::class)) {
            return \Moox\Demo\Seeding\SeedingConfig::resolveCount('product', self::DEFAULT_PRODUCT_COUNT);
        }

        return self::DEFAULT_PRODUCT_COUNT;
    }

    private function localizedTitle(string $locale): string
    {
        return Str::title($this->fakerForLocale($locale)->words(random_int(2, 5), true));
    }

    private function localizedSubtitle(string $locale): string
    {
        return $this->fakerForLocale($locale)->sentence(6);
    }

    private function localizedExcerpt(string $locale): string
    {
        return $this->fakerForLocale($locale)->text(180);
    }

    private function localizedDescription(string $locale): string
    {
        return $this->fakerForLocale($locale)->paragraph(2);
    }

    private function localizedContent(string $locale): string
    {
        return $this->fakerForLocale($locale)->paragraphs(random_int(2, 5), true);
    }

    private function fakerForLocale(string $locale): Generator
    {
        return FakerFactory::create(self::FAKER_LOCALE_MAP[$locale] ?? 'en_US');
    }
}
