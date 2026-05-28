<?php

declare(strict_types=1);

namespace Moox\Tag\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Moox\Demo\Seeding\RunsMooxDemoAssets;
use Moox\Demo\Seeding\SeedingConfig;
use Moox\Demo\Seeding\SeedOutput;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;
use Moox\Tag\Models\Tag;
use Moox\User\Models\User;

class TagSeeder extends Seeder
{
    public const DEMO_SLUG_PREFIX = 'demo-tag';

    public const DEFAULT_TAG_COUNT = 100;

    /** @var list<string> */
    public const LOCALES = ['cs_CZ', 'en_US', 'de_DE', 'pl_PL'];

    /** @var list<string> */
    private const TAG_STATUSES = ['draft', 'waiting', 'private', 'scheduled', 'published'];

    private const MEDIA_ATTACH_PROBABILITY = 0.8;

    public function run(): void
    {
        $this->seed();

        if (class_exists(RunsMooxDemoAssets::class)) {
            RunsMooxDemoAssets::invoke($this);
        }
    }

    protected function seed(): void
    {
        $this->purgeDemoTags();

        $faker = fake();
        $author = User::query()->first();
        $count = $this->resolveTagCount();
        $mediaPool = $this->loadImageMediaPool();
        $created = 0;
        $withMedia = 0;

        if ($mediaPool->isEmpty()) {
            $this->command?->warn('No images in media table - tags will be seeded without media_usables.');
        }

        for ($index = 1; $index <= $count; $index++) {
            $status = $faker->randomElement(self::TAG_STATUSES);

            $tag = Tag::query()->create([
                'is_active' => $faker->boolean(85),
                'color' => $faker->hexColor(),
                'weight' => $faker->numberBetween(1, 10),
                'count' => $faker->numberBetween(0, 100),
                'status' => $status,
                'due_at' => $faker->optional(0.25)->dateTimeBetween('now', '+60 days'),
                'custom_properties' => [
                    'seed_source' => 'tag_seeder_v1',
                    'seed_index' => $index,
                ],
            ]);

            foreach (self::LOCALES as $locale) {
                $title = $this->localizedTitle($locale);
                $slug = self::DEMO_SLUG_PREFIX
                    .'-'.Str::slug($title)
                    .'-'.Str::lower($locale)
                    .'-'.sprintf('%04d', $index);

                $translation = $tag->translateOrNew($locale);
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

            $tag->save();

            if ($mediaPool->isNotEmpty() && $faker->boolean((int) (self::MEDIA_ATTACH_PROBABILITY * 100))) {
                /** @var Media $media */
                $media = $mediaPool->random();

                MediaUsable::query()->firstOrCreate([
                    'media_id' => $media->getKey(),
                    'media_usable_id' => $tag->getKey(),
                    'media_usable_type' => Tag::class,
                ]);

                $withMedia++;
            }

            $created++;
            $this->reportCreated("Tag {$tag->getKey()}");
        }

        $this->reportDetail(sprintf(
            '%d faker tag(s) seeded across %d locale(s), %d with media link(s).',
            $created,
            count(self::LOCALES),
            $withMedia
        ));
    }

    private function purgeDemoTags(): void
    {
        Tag::query()
            ->whereHas('translations', function ($query): void {
                $query->where('slug', 'like', self::DEMO_SLUG_PREFIX.'-%');
            })
            ->forceDelete();
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

    private function resolveTagCount(): int
    {
        if (class_exists(SeedingConfig::class)) {
            return SeedingConfig::resolveCount('tag', self::DEFAULT_TAG_COUNT);
        }

        return self::DEFAULT_TAG_COUNT;
    }

    /**
     * @return Collection<int, Media>
     */
    private function loadImageMediaPool(): Collection
    {
        $ids = Media::query()
            ->where(function ($query): void {
                $query
                    ->where('mime_type', 'like', 'image/%')
                    ->orWhereIn('mime_type', ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml']);
            })
            ->pluck('id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return Media::query()->whereIn('id', $ids)->get();
    }

    private function localizedTitle(string $locale): string
    {
        return match ($locale) {
            'de_DE' => sprintf(
                '%s %s',
                $this->randomElement(['Thema', 'Schlagwort', 'Merkmal', 'Label', 'Kategoriehinweis']),
                $this->randomElement(['Marketing', 'System', 'Freigabe', 'Monitoring', 'Import'])
            ),
            'fr_FR' => sprintf(
                '%s %s',
                $this->randomElement(['Sujet', 'Etiquette', 'Motcle', 'Repere', 'Tag']),
                $this->randomElement(['marketing', 'systeme', 'validation', 'monitoring', 'import'])
            ),
            'es_ES' => sprintf(
                '%s %s',
                $this->randomElement(['Tema', 'Etiqueta', 'Palabraclave', 'Marca', 'Tag']),
                $this->randomElement(['marketing', 'sistema', 'aprobacion', 'monitorizacion', 'importacion'])
            ),
            default => sprintf(
                '%s %s',
                $this->randomElement(['Topic', 'Tag', 'Label', 'Marker', 'Keyword']),
                $this->randomElement(['marketing', 'system', 'approval', 'monitoring', 'import'])
            ),
        };
    }

    private function localizedDescription(string $locale): string
    {
        return match ($locale) {
            'de_DE' => $this->randomElement([
                'Dieser Tag klassifiziert Inhalte fuer bessere Filterung und Navigation.',
                'Dieser Tag wird zur thematischen Zuordnung in Listen und Uebersichten genutzt.',
                'Dieser Tag dient als organisatorischer Marker fuer redaktionelle Inhalte.',
            ]),
            'fr_FR' => $this->randomElement([
                'Ce tag classe les contenus pour une meilleure navigation.',
                'Ce tag est utilise pour organiser les listes par sujet.',
                'Ce tag sert de repere organisationnel pour les contenus editoriaux.',
            ]),
            'es_ES' => $this->randomElement([
                'Este tag clasifica contenidos para mejorar la navegacion.',
                'Este tag se usa para organizar listados por tema.',
                'Este tag funciona como marcador organizativo para contenidos editoriales.',
            ]),
            default => $this->randomElement([
                'This tag classifies content for better filtering and navigation.',
                'This tag is used to organize list views by topic.',
                'This tag acts as an organizational marker for editorial content.',
            ]),
        };
    }

    private function localizedContent(string $locale): string
    {
        return match ($locale) {
            'de_DE' => implode("\n", [
                '## Einsatz',
                '- Gruppierung verwandter Inhalte',
                '- Verbesserte Suche und Filter',
                '- Konsistente redaktionelle Kennzeichnung',
            ]),
            'fr_FR' => implode("\n", [
                '## Utilisation',
                '- Regrouper les contenus lies',
                '- Ameliorer recherche et filtres',
                '- Uniformiser le marquage editorial',
            ]),
            'es_ES' => implode("\n", [
                '## Uso',
                '- Agrupar contenidos relacionados',
                '- Mejorar busqueda y filtros',
                '- Estandarizar el marcado editorial',
            ]),
            default => implode("\n", [
                '## Usage',
                '- Group related content',
                '- Improve search and filters',
                '- Standardize editorial labeling',
            ]),
        };
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
