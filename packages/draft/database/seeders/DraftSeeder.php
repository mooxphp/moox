<?php

declare(strict_types=1);

namespace Moox\Draft\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Moox\Draft\Models\Draft;
use Moox\User\Models\User;

class DraftSeeder extends Seeder
{
    public const DEMO_SLUG_PREFIX = 'demo-draft';

    public const DEFAULT_DRAFT_COUNT = 100;

    /** @var list<string> */
    public const LOCALES = ['cs_CZ', 'en_US', 'de_DE', 'pl_PL'];

    /** @var list<string> */
    private const TYPES = ['article', 'page', 'post', 'news', 'tutorial'];

    /** @var list<string> */
    private const TRANSLATION_STATUSES = ['draft', 'waiting', 'private', 'scheduled', 'published'];

    public function run(): void
    {
        $this->seed();

        if (class_exists(\Moox\Demo\Seeding\RunsMooxDemoAssets::class)) {
            \Moox\Demo\Seeding\RunsMooxDemoAssets::invoke($this);
        }
    }

    protected function seed(): void
    {
        $this->purgeDemoDrafts();

        $count = $this->resolveDraftCount();
        $author = User::query()->first();
        $created = 0;
        $faker = fake();

        for ($index = 1; $index <= $count; $index++) {
            $status = $faker->randomElement(self::TRANSLATION_STATUSES);
            $draft = Draft::query()->create([
                'is_active' => $faker->boolean(85),
                'type' => $faker->randomElement(self::TYPES),
                'color' => $faker->hexColor(),
                'status' => $status,
                'due_at' => $faker->optional(0.4)->dateTimeBetween('now', '+45 days'),
                'image' => [
                    'url' => $faker->imageUrl(1200, 630),
                    'alt' => $faker->sentence(4),
                ],
                'data' => json_encode([
                    'seed_source' => 'draft_seeder_v1',
                    'seed_index' => $index,
                ], JSON_THROW_ON_ERROR),
            ]);

            foreach (self::LOCALES as $locale) {
                $title = $this->localizedTitle($locale);
                $slug = self::DEMO_SLUG_PREFIX
                    .'-'.Str::slug($title)
                    .'-'.Str::lower($locale)
                    .'-'.sprintf('%04d', $index);

                $translation = $draft->translateOrNew($locale);
                $translation->title = $title;
                $translation->slug = Str::limit($slug, 180, '');
                $translation->permalink = rtrim((string) config('app.url'), '/').'/'.$locale.'/'.$translation->slug;
                $translation->description = $this->localizedDescription($locale);
                $translation->content = $this->localizedContent($locale);
                $translation->translation_status = $status;

                if ($author !== null) {
                    $translation->author_id = $author->getKey();
                    $translation->author_type = $author->getMorphClass();
                }
            }

            $draft->save();
            $created++;

            $this->reportCreated("Draft {$draft->getKey()}");
        }

        $this->reportDetail(sprintf(
            '%d faker draft(s) seeded across %d locale(s).',
            $created,
            count(self::LOCALES)
        ));
    }

    private function purgeDemoDrafts(): void
    {
        Draft::query()
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

    private function resolveDraftCount(): int
    {
        if (class_exists(\Moox\Demo\Seeding\SeedingConfig::class)) {
            return \Moox\Demo\Seeding\SeedingConfig::resolveCount('draft', self::DEFAULT_DRAFT_COUNT);
        }

        return self::DEFAULT_DRAFT_COUNT;
    }

    private function localizedTitle(string $locale): string
    {
        return match ($locale) {
            'de_DE' => sprintf(
                '%s %s %s',
                $this->randomElement(['Entwurf', 'Notiz', 'Artikel', 'Dokument', 'Beitrag']),
                $this->randomElement(['zur', 'fuer', 'mit', 'ohne']),
                $this->randomElement(['Freigabe', 'Abstimmung', 'Redaktion', 'Kampagne', 'Planung'])
            ),
            'fr_FR' => sprintf(
                '%s %s %s',
                $this->randomElement(['Brouillon', 'Article', 'Note', 'Document', 'Publication']),
                $this->randomElement(['pour', 'avec', 'sans', 'sur']),
                $this->randomElement(['validation', 'revision', 'campagne', 'publication', 'planification'])
            ),
            'es_ES' => sprintf(
                '%s %s %s',
                $this->randomElement(['Borrador', 'Articulo', 'Nota', 'Documento', 'Publicacion']),
                $this->randomElement(['para', 'con', 'sin', 'sobre']),
                $this->randomElement(['revision', 'aprobacion', 'campana', 'publicacion', 'planificacion'])
            ),
            default => sprintf(
                '%s %s %s',
                $this->randomElement(['Draft', 'Article', 'Note', 'Document', 'Post']),
                $this->randomElement(['for', 'with', 'without', 'about']),
                $this->randomElement(['review', 'approval', 'campaign', 'publication', 'planning'])
            ),
        };
    }

    private function localizedDescription(string $locale): string
    {
        return match ($locale) {
            'de_DE' => sprintf(
                '%s %s',
                $this->randomElement([
                    'Dieser Entwurf dient als Arbeitsgrundlage fuer die naechste Version.',
                    'Dieser Inhalt wurde fuer eine interne Redaktionsrunde vorbereitet.',
                    'Dieser Text ist fuer einen spaeteren Freigabeprozess vorgesehen.',
                ]),
                $this->randomElement([
                    'Bitte Struktur, Tonalitaet und Vollstaendigkeit pruefen.',
                    'Bitte offene Punkte markieren und priorisieren.',
                    'Bitte Fakten und Quellen vor der Veroeffentlichung verifizieren.',
                ])
            ),
            'fr_FR' => sprintf(
                '%s %s',
                $this->randomElement([
                    'Ce brouillon sert de base de travail pour la prochaine version.',
                    'Ce contenu a ete prepare pour une revue editoriale interne.',
                    'Ce texte est prevu pour une validation ulterieure.',
                ]),
                $this->randomElement([
                    'Merci de verifier la structure, le ton et la coherence.',
                    'Merci de signaler les points ouverts et les priorites.',
                    'Merci de confirmer les faits et les sources avant publication.',
                ])
            ),
            'es_ES' => sprintf(
                '%s %s',
                $this->randomElement([
                    'Este borrador sirve como base de trabajo para la siguiente version.',
                    'Este contenido fue preparado para una revision editorial interna.',
                    'Este texto esta previsto para una aprobacion posterior.',
                ]),
                $this->randomElement([
                    'Por favor revisa estructura, tono y coherencia.',
                    'Por favor marca los puntos abiertos y sus prioridades.',
                    'Por favor verifica datos y fuentes antes de publicar.',
                ])
            ),
            default => sprintf(
                '%s %s',
                $this->randomElement([
                    'This draft serves as a working base for the next revision.',
                    'This content was prepared for an internal editorial review.',
                    'This text is intended for a later approval step.',
                ]),
                $this->randomElement([
                    'Please review structure, tone, and completeness.',
                    'Please mark open points and priorities.',
                    'Please verify facts and sources before publishing.',
                ])
            ),
        };
    }

    private function localizedContent(string $locale): string
    {
        $lines = match ($locale) {
            'de_DE' => [
                '# '.$this->localizedTitle($locale),
                '',
                '## Zusammenfassung',
                $this->localizedDescription($locale),
                '',
                '## Naechste Schritte',
                '- Inhalt redaktionell abstimmen',
                '- Rueckmeldungen aus dem Team einarbeiten',
                '- Freigabetermin planen',
            ],
            'fr_FR' => [
                '# '.$this->localizedTitle($locale),
                '',
                '## Resume',
                $this->localizedDescription($locale),
                '',
                '## Prochaines etapes',
                '- Aligner le contenu avec la redaction',
                '- Integrer les retours de l equipe',
                '- Planifier la date de validation',
            ],
            'es_ES' => [
                '# '.$this->localizedTitle($locale),
                '',
                '## Resumen',
                $this->localizedDescription($locale),
                '',
                '## Siguientes pasos',
                '- Alinear el contenido con el equipo editorial',
                '- Integrar los comentarios del equipo',
                '- Planificar la fecha de aprobacion',
            ],
            default => [
                '# '.$this->localizedTitle($locale),
                '',
                '## Summary',
                $this->localizedDescription($locale),
                '',
                '## Next steps',
                '- Align content with the editorial team',
                '- Integrate team feedback',
                '- Plan the approval date',
            ],
        };

        return implode("\n", $lines);
    }

    /**
     * @template T
     *
     * @param  list<T>  $items
     * @return T
     */
    private function randomElement(array $items): mixed
    {
        return $items[array_rand($items)];
    }
}
