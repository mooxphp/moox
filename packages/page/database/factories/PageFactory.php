<?php

namespace Moox\Page\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Moox\Page\Models\Page;
use Moox\Page\Models\PageTranslation;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * Central locale configuration
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
            // Base model attributes (non-translated)
            'is_active' => $this->faker->boolean(80),

            'image' => [
                'url' => $this->faker->imageUrl(800, 600, 'business'),
                'alt' => $this->faker->sentence(4),
                'caption' => $this->faker->optional()->sentence(),
            ],
            'type' => $this->faker->randomElement(['article', 'page', 'post', 'news', 'tutorial']),
            'color' => $this->faker->hexColor(),
            'due_at' => $this->faker->optional(0.3)->dateTimeBetween('now', '+30 days'),
            'status' => $this->faker->randomElement(['draft', 'published', 'scheduled', 'waiting', 'private']),
            'custom_properties' => [
                'theme' => $this->faker->randomElement(['light', 'dark', 'auto']),
                'layout' => $this->faker->randomElement(['grid', 'list', 'masonry']),
                'show_author' => $this->faker->boolean(),
                'allow_comments' => $this->faker->boolean(70),
            ],
        ];
    }

    /**
     * Configure the factory to handle translations automatically
     */
    public function configure()
    {
        return $this->afterCreating(function (Page $page) {
            // Set translated attributes directly on the model after it's created
            $this->setTranslatedAttributes($page);
        });
    }

    /**
     * Set translated attributes using Astrotomic's methods
     */
    private function setTranslatedAttributes(Page $page): void
    {
        $locales = $this->getLocales();

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
                'translation_status' => $this->faker->randomElement(['draft', 'waiting', 'private', 'scheduled', 'published', 'not_translated', 'deleted']),
            ]);
            $page->save();
        }
    }

    /**
     * Get locales to create translations for
     */
    private function getLocales(): array
    {
        return array_keys(self::LOCALES);
    }

    /**
     * Get localized title based on locale
     */
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

    /**
     * Get localized slug based on locale
     */
    private function getLocalizedSlug(string $locale): string
    {
        $baseSlug = $this->faker->slug(3);

        return $baseSlug.'-'.$locale;
    }

    /**
     * Get localized description based on locale
     */
    private function getLocalizedDescription(string $locale): string
    {
        $baseDescription = $this->faker->paragraph(2);
        $suffix = isset(self::LOCALES[$locale]) ? ' ('.self::LOCALES[$locale].' )' : '';

        return $baseDescription.$suffix;
    }

    /**
     * Get localized content based on locale
     */
    private function getLocalizedContent(string $locale): string
    {
        $content = $this->faker->paragraphs(rand(3, 8), true);

        $suffix = isset(self::LOCALES[$locale]) ? ' ('.self::LOCALES[$locale].' )' : '';

        return $content.$suffix;
    }

    /**
     * Create a published page
     */
    public function published(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'published',
            ];
        })->afterCreating(function (Page $page) {
            // Override translation status for published
            foreach ($page->translations as $translation) {
                if (! $translation instanceof PageTranslation) {
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

    /**
     * Create a scheduled page
     */
    public function scheduled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'scheduled',
            ];
        })->afterCreating(function (Page $page) {
            // Override translation status for scheduled
            foreach ($page->translations as $translation) {
                if (! $translation instanceof PageTranslation) {
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

    /**
     * Create a page with specific locales
     */
    public function withLocales(array $locales): static
    {
        return $this->afterCreating(function (Page $page) use ($locales) {
            // Clear existing translations
            $page->deleteTranslations();

            // Create only specified locales
            foreach ($locales as $locale) {
                $page->translateOrNew($locale)->fill([
                    'title' => $this->getLocalizedTitle($locale),
                    'slug' => $this->getLocalizedSlug($locale),
                    'permalink' => $this->faker->url(),
                    'description' => $this->getLocalizedDescription($locale),
                    'content' => $this->getLocalizedContent($locale),
                    'author_id' => $this->faker->numberBetween(1, 10),
                    'author_type' => array_key_first(config('page.user_models')),
                    'translation_status' => 'draft',
                ]);
                $page->save();
            }
        });
    }

    /**
     * Create a page with only English translation
     */
    public function englishOnly(): static
    {
        return $this->withLocales(['en']);
    }

    /**
     * Create a page with German and English
     */
    public function bilingual(): static
    {
        return $this->withLocales(['en', 'de']);
    }

    /**
     * Create a page with all supported languages
     */
    public function multilingual(): static
    {
        return $this->withLocales(['en', 'de', 'fr', 'es']);
    }
}
