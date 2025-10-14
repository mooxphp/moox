<?php

namespace Moox\Draft\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Draft\Models\Draft;

class DraftFactory extends Factory
{
    protected $model = Draft::class;

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
            'status' => $this->faker->randomElement(),
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
        return $this->afterMaking(function (Draft $draft) {
            // Set translated attributes directly on the model
            $this->setTranslatedAttributes($draft);
        });
    }

    /**
     * Set translated attributes using Astrotomic's methods
     */
    private function setTranslatedAttributes(Draft $draft): void
    {
        $locales = $this->getLocales();

        $userModel = array_key_first(config('draft.user_models'));

        foreach ($locales as $locale) {
            $draft->translateOrNew($locale)->fill([
                'title' => $this->getLocalizedTitle($locale),
                'slug' => $this->getLocalizedSlug($locale),
                'permalink' => $this->faker->url(),
                'description' => $this->getLocalizedDescription($locale),
                'content' => $this->getLocalizedContent($locale),
                'author_id' => $this->faker->numberBetween(1, 10),
                'author_type' => $userModel,
                'translation_status' => $this->faker->randomElement(['draft', 'waiting', 'private', 'scheduled', 'published', 'not_translated', 'deleted']),

            ]);
        }
    }

    /**
     * Get locales to create translations for
     */
    private function getLocales(): array
    {
        $locales = ['en_us']; // Always create English

        $locales[] = 'de_de';
        $locales[] = 'fr_fr';
        $locales[] = 'es_es';

        return $locales;
    }

    /**
     * Get localized title based on locale
     */
    private function getLocalizedTitle(string $locale): string
    {
        $titles = [
            'en' => $this->faker->sentence(rand(3, 8)),
            'de' => $this->faker->sentence(rand(3, 8)).' (Deutsch)',
            'fr' => $this->faker->sentence(rand(3, 8)).' (Français)',
            'es' => $this->faker->sentence(rand(3, 8)).' (Español)',
        ];

        return $titles[$locale] ?? $titles['en'];
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
        $descriptions = [
            'en' => $this->faker->paragraph(2),
            'de' => $this->faker->paragraph(2).' (Deutsche Beschreibung)',
            'fr' => $this->faker->paragraph(2).' (Description française)',
            'es' => $this->faker->paragraph(2).' (Descripción española)',
        ];

        return $descriptions[$locale] ?? $descriptions['en'];
    }

    /**
     * Get localized content based on locale
     */
    private function getLocalizedContent(string $locale): string
    {
        $content = $this->faker->paragraphs(rand(3, 8), true);

        $localizedSuffix = [
            'de' => ' (Deutscher Inhalt)',
            'fr' => ' (Contenu français)',
            'es' => ' (Contenido español)',
        ];

        return $content.($localizedSuffix[$locale] ?? '');
    }

    /**
     * Create a published draft
     */
    public function published(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'published',
            ];
        })->afterMaking(function (Draft $draft) {
            // Override translation status for published
            foreach ($draft->translations as $translation) {
                $translation->translation_status = 'published';
                $translation->published_at = $this->faker->dateTimeBetween('-30 days', 'now');
            }
        });
    }

    /**
     * Create a scheduled draft
     */
    public function scheduled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'scheduled',
            ];
        })->afterMaking(function (Draft $draft) {
            // Override translation status for scheduled
            foreach ($draft->translations as $translation) {
                $translation->translation_status = 'scheduled';
                $translation->to_publish_at = $this->faker->dateTimeBetween('now', '+7 days');
            }
        });
    }

    /**
     * Create a draft with specific locales
     */
    public function withLocales(array $locales): static
    {
        return $this->afterMaking(function (Draft $draft) use ($locales) {
            // Clear existing translations
            $draft->deleteTranslations();

            // Create only specified locales
            foreach ($locales as $locale) {
                $draft->translateOrNew($locale)->fill([
                    'title' => $this->getLocalizedTitle($locale),
                    'slug' => $this->getLocalizedSlug($locale),
                    'permalink' => $this->faker->url(),
                    'description' => $this->getLocalizedDescription($locale),
                    'content' => $this->getLocalizedContent($locale),
                    'author_id' => $this->faker->numberBetween(1, 10),
                    'author_type' => 'Moox\\User\\Models\\User',
                    'translation_status' => 'draft',
                ]);
            }
        });
    }

    /**
     * Create a draft with only English translation
     */
    public function englishOnly(): static
    {
        return $this->withLocales(['en']);
    }

    /**
     * Create a draft with German and English
     */
    public function bilingual(): static
    {
        return $this->withLocales(['en', 'de']);
    }

    /**
     * Create a draft with all supported languages
     */
    public function multilingual(): static
    {
        return $this->withLocales(['en', 'de', 'fr', 'es']);
    }
}
