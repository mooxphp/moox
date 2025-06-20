<?php

namespace Moox\Tag\Database\Factories;

use InvalidArgumentException;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Tag\Models\Tag;
use Moox\Tag\Models\TagTranslation;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * Available locales with their configuration
     */
    protected array $availableLocales = [
        'en' => [
            'faker_locale' => 'en_US',
            'sample_words' => ['Important', 'Urgent', 'Draft', 'Review', 'Approved', 'Featured', 'Archive'],
            'sample_content' => ['Project documentation', 'Meeting notes', 'Task overview', 'Status update'],
        ],
        'de' => [
            'faker_locale' => 'de_DE',
            'sample_words' => ['Wichtig', 'Dringend', 'Entwurf', 'Prüfung', 'Genehmigt', 'Hervorgehoben', 'Archiv'],
            'sample_content' => ['Projektdokumentation', 'Besprechungsnotizen', 'Aufgabenübersicht', 'Statusaktualisierung'],
        ],
        'fr' => [
            'faker_locale' => 'fr_FR',
            'sample_words' => ['Important', 'Urgent', 'Brouillon', 'Révision', 'Approuvé', 'En vedette', 'Archive'],
            'sample_content' => ['Documentation du projet', 'Notes de réunion', 'Aperçu des tâches', 'Mise à jour du statut'],
        ],
        'es' => [
            'faker_locale' => 'es_ES',
            'sample_words' => ['Importante', 'Urgente', 'Borrador', 'Revisión', 'Aprobado', 'Destacado', 'Archivo'],
            'sample_content' => ['Documentación del proyecto', 'Notas de reunión', 'Resumen de tareas', 'Actualización de estado'],
        ],
        'it' => [
            'faker_locale' => 'it_IT',
            'sample_words' => ['Importante', 'Urgente', 'Bozza', 'Revisione', 'Approvato', 'In evidenza', 'Archivio'],
            'sample_content' => ['Documentazione del progetto', 'Note della riunione', 'Panoramica delle attività', 'Aggiornamento dello stato'],
        ],
        'nl' => [
            'faker_locale' => 'nl_NL',
            'sample_words' => ['Belangrijk', 'Dringend', 'Concept', 'Review', 'Goedgekeurd', 'Uitgelicht', 'Archief'],
            'sample_content' => ['Projectdocumentatie', 'Vergadernotities', 'Taakoverzicht', 'Statusupdate'],
        ],
    ];

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'color' => $this->faker->hexColor(),
            'weight' => $this->faker->numberBetween(1, 10),
            'count' => $this->faker->numberBetween(0, 100),
            'featured_image_url' => $this->faker->imageUrl(),
        ];
    }

    /**
     * Configure the model's state
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Tag $tag) {
            // Always create English translation by default
            $title = $this->generateLocalizedTitle('en');
            $slug = TagTranslation::generateUniqueSlug($title, 'en');

            $tag->translateOrNew('en')->fill([
                'title' => $title,
                'slug' => $slug,
                'content' => $this->generateLocalizedContent('en'),
            ])->save();
        });
    }

    /**
     * Generate a localized title
     */
    protected function generateLocalizedTitle(string $locale): string
    {
        $faker = FakerFactory::create($this->availableLocales[$locale]['faker_locale']);
        $sampleWords = $this->availableLocales[$locale]['sample_words'];

        // Mix faker words with sample words for more realistic content
        if ($this->faker->boolean(30)) {
            return $this->faker->randomElement($sampleWords);
        }

        return $faker->words(2, true);
    }

    /**
     * Generate localized content
     */
    protected function generateLocalizedContent(string $locale): string
    {
        $faker = FakerFactory::create($this->availableLocales[$locale]['faker_locale']);
        $sampleContent = $this->availableLocales[$locale]['sample_content'];

        // Mix faker content with sample content for more realistic results
        if ($this->faker->boolean(30)) {
            return $this->faker->randomElement($sampleContent);
        }

        return $faker->paragraph();
    }

    /**
     * Add translation for a specific locale
     */
    public function withTranslation(string $locale): self
    {
        if (! isset($this->availableLocales[$locale])) {
            throw new InvalidArgumentException("Locale {$locale} is not supported");
        }

        return $this->afterCreating(function (Tag $tag) use ($locale) {
            $title = $this->generateLocalizedTitle($locale);
            $slug = TagTranslation::generateUniqueSlug($title, $locale);

            $tag->translateOrNew($locale)->fill([
                'title' => $title,
                'slug' => $slug,
                'content' => $this->generateLocalizedContent($locale),
            ])->save();
        });
    }

    /**
     * Add German translation
     */
    public function withGermanTranslation(): self
    {
        return $this->withTranslation('de');
    }

    /**
     * Add French translation
     */
    public function withFrenchTranslation(): self
    {
        return $this->withTranslation('fr');
    }

    /**
     * Add Spanish translation
     */
    public function withSpanishTranslation(): self
    {
        return $this->withTranslation('es');
    }

    /**
     * Add Italian translation
     */
    public function withItalianTranslation(): self
    {
        return $this->withTranslation('it');
    }

    /**
     * Add Dutch translation
     */
    public function withDutchTranslation(): self
    {
        return $this->withTranslation('nl');
    }

    /**
     * Add translations for all supported languages
     */
    public function withAllTranslations(): self
    {
        return $this->afterCreating(function (Tag $tag) {
            foreach ($this->availableLocales as $locale => $config) {
                if ($locale === 'en') {
                    continue;
                } // Skip English as it's already created

                $title = $this->generateLocalizedTitle($locale);
                $slug = TagTranslation::generateUniqueSlug($title, $locale);

                $tag->translateOrNew($locale)->fill([
                    'title' => $title,
                    'slug' => $slug,
                    'content' => $this->generateLocalizedContent($locale),
                ])->save();
            }
        });
    }

    /**
     * Add translations for random languages
     */
    public function withRandomTranslations(int $count = 2): self
    {
        return $this->afterCreating(function (Tag $tag) use ($count) {
            $locales = array_keys($this->availableLocales);
            unset($locales[array_search('en', $locales)]); // Remove English
            $selectedLocales = array_rand(array_flip($locales), min($count, count($locales)));

            foreach ((array) $selectedLocales as $locale) {
                $this->withTranslation($locale)->configure()->afterCreating->first()($tag);
            }
        });
    }

    /**
     * Configure the model as featured
     */
    public function featured(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'weight' => $this->faker->numberBetween(8, 10),
                'featured_image_url' => $this->faker->imageUrl(1200, 630),
            ];
        });
    }

    /**
     * Configure the model as a system tag
     */
    public function system(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'weight' => 1,
                'color' => '#FF0000',
            ];
        });
    }
}
