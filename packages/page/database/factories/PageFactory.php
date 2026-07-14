<?php

namespace Moox\Page\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Moox\Page\Models\Page;
use Moox\Page\Support\PageModels;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    public function modelName(): string
    {
        return PageModels::page();
    }

    /**
     * @var array<string, string>
     */
    private const LOCALES = [
        'en_us' => 'English',
        'de_de' => 'Deutsch',
        'fr_fr' => 'Français',
        'es_es' => 'Español',
    ];

    public function definition(): array
    {
        return [
            'is_active' => $this->faker->boolean(80),
            'is_startpage' => false,
            'layout' => $this->faker->randomElement(array_keys(Page::layoutOptions()) ?: ['default']),
            'image' => [
                'url' => $this->faker->imageUrl(800, 600, 'business'),
                'alt' => $this->faker->sentence(4),
                'caption' => $this->faker->optional()->sentence(),
            ],
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Page $page): void {
            $this->setTranslatedAttributes($page);
        });
    }

    private function setTranslatedAttributes(Page $page): void
    {
        $userModel = array_key_first(config('page.user_models'));

        foreach ($this->getLocales() as $locale) {
            $page->translateOrNew($locale)->fill([
                'title' => $this->getLocalizedTitle($locale),
                'slug' => $this->getLocalizedSlug($locale),
                'permalink' => $this->faker->url(),
                'description' => $this->getLocalizedDescription($locale),
                'content' => $this->getLocalizedContent($locale),
                'author_id' => $this->faker->numberBetween(1, 10),
                'author_type' => $userModel,
                'translation_status' => $this->faker->randomElement(['draft', 'waiting', 'private', 'scheduled', 'published', 'not_translated', 'deleted']),
            ]);
            $page->save();
        }
    }

    /**
     * @return list<string>
     */
    private function getLocales(): array
    {
        return array_keys(self::LOCALES);
    }

    private function getLocalizedTitle(string $locale): string
    {
        $titles = [
            'en_us' => $this->faker->sentence(rand(3, 8)).' (English)',
            'de_de' => $this->faker->sentence(rand(3, 8)).' (Deutsch)',
            'fr_fr' => $this->faker->sentence(rand(3, 8)).' (Français)',
            'es_es' => $this->faker->sentence(rand(3, 8)).' (Español)',
        ];

        return $titles[$locale] ?? $titles['en_us'];
    }

    private function getLocalizedSlug(string $locale): string
    {
        return $this->faker->slug(3).'-'.$locale;
    }

    private function getLocalizedDescription(string $locale): string
    {
        $suffix = isset(self::LOCALES[$locale]) ? ' ('.self::LOCALES[$locale].' )' : '';

        return $this->faker->paragraph(2).$suffix;
    }

    private function getLocalizedContent(string $locale): string
    {
        $suffix = isset(self::LOCALES[$locale]) ? ' ('.self::LOCALES[$locale].' )' : '';

        return $this->faker->paragraphs(rand(3, 8), true).$suffix;
    }

    public function homepage(): static
    {
        return $this->state(fn (): array => [
            'is_startpage' => true,
        ]);
    }

    public function published(): static
    {
        return $this->afterCreating(function (Page $page): void {
            foreach ($page->translations as $translation) {
                if (! is_a($translation, PageModels::pageTranslation())) {
                    continue;
                }

                $translation->translation_status = 'published';
                $translation->published_at = Carbon::instance(
                    $this->faker->dateTimeBetween('-30 days', 'now')
                );
                $translation->save();
            }
        });
    }

    public function scheduled(): static
    {
        return $this->afterCreating(function (Page $page): void {
            foreach ($page->translations as $translation) {
                if (! is_a($translation, PageModels::pageTranslation())) {
                    continue;
                }

                $translation->translation_status = 'scheduled';
                $translation->to_publish_at = Carbon::instance(
                    $this->faker->dateTimeBetween('now', '+7 days')
                );
                $translation->save();
            }
        });
    }

    public function withLocales(array $locales): static
    {
        return $this->afterCreating(function (Page $page) use ($locales): void {
            $page->deleteTranslations();

            $userModel = array_key_first(config('page.user_models'));

            foreach ($locales as $locale) {
                $page->translateOrNew($locale)->fill([
                    'title' => $this->getLocalizedTitle($locale),
                    'slug' => $this->getLocalizedSlug($locale),
                    'permalink' => $this->faker->url(),
                    'description' => $this->getLocalizedDescription($locale),
                    'content' => $this->getLocalizedContent($locale),
                    'author_id' => $this->faker->numberBetween(1, 10),
                    'author_type' => $userModel,
                    'translation_status' => 'draft',
                ]);
                $page->save();
            }
        });
    }

    public function englishOnly(): static
    {
        return $this->withLocales(['en_us']);
    }

    public function bilingual(): static
    {
        return $this->withLocales(['en_us', 'de_de']);
    }

    public function multilingual(): static
    {
        return $this->withLocales(array_keys(self::LOCALES));
    }
}
