<?php

declare(strict_types=1);

namespace Moox\Record\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Moox\Record\Enums\RecordStatus;
use Moox\Record\Models\Record;
use Moox\User\Models\User;

class RecordSeeder extends Seeder
{
    public const DEMO_SLUG_PREFIX = 'demo-record';

    public const DEFAULT_RECORD_COUNT = 100;

    /** @var list<string> */
    public const LOCALES = ['cs_CZ', 'en_US', 'de_DE', 'pl_PL'];

    public function run(): void
    {
        $this->seed();

        if (class_exists(\Moox\Demo\Seeding\RunsMooxDemoAssets::class)) {
            \Moox\Demo\Seeding\RunsMooxDemoAssets::invoke($this);
        }
    }

    protected function seed(): void
    {
        $this->purgeDemoRecords();

        $count = $this->resolveRecordCount();
        $author = User::query()->first();
        $faker = fake();
        $created = 0;

        for ($index = 1; $index <= $count; $index++) {
            $locale = self::LOCALES[array_rand(self::LOCALES)];
            $title = $this->localizedTitle($locale);
            $slug = self::DEMO_SLUG_PREFIX
                .'-'.Str::slug($title)
                .'-'.Str::lower($locale)
                .'-'.sprintf('%04d', $index);
            $status = $faker->randomElement([
                RecordStatus::ACTIVE->value,
                RecordStatus::INACTIVE->value,
                RecordStatus::ARCHIVED->value,
            ]);

            $record = Record::query()->create([
                'title' => $title,
                'slug' => Str::limit($slug, 180, ''),
                'description' => $this->localizedDescription($locale),
                'permalink' => rtrim((string) config('app.url'), '/').'/'.$locale.'/'.$slug,
                'status' => $status,
                'custom_properties' => [
                    'seed_source' => 'record_seeder_v1',
                    'seed_index' => $index,
                    'seed_locale' => $locale,
                ],
                'author_id' => $author?->getKey(),
                'author_type' => $author?->getMorphClass(),
            ]);

            $created++;
            $this->reportCreated("Record {$record->getKey()}");
        }

        $this->reportDetail(sprintf(
            '%d faker record(s) seeded across %d locale(s).',
            $created,
            count(self::LOCALES)
        ));
    }

    private function purgeDemoRecords(): void
    {
        Record::query()
            ->where('slug', 'like', self::DEMO_SLUG_PREFIX.'-%')
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

    private function resolveRecordCount(): int
    {
        if (class_exists(\Moox\Demo\Seeding\SeedingConfig::class)) {
            return \Moox\Demo\Seeding\SeedingConfig::resolveCount('record', self::DEFAULT_RECORD_COUNT);
        }

        return self::DEFAULT_RECORD_COUNT;
    }

    private function localizedTitle(string $locale): string
    {
        return match ($locale) {
            'de_DE' => sprintf(
                '%s %s %s',
                $this->randomElement(['Einstellung', 'Eintrag', 'Datensatz', 'Konfiguration', 'Parameter']),
                $this->randomElement(['fuer', 'mit', 'ohne', 'zu']),
                $this->randomElement(['System', 'Freigabe', 'Import', 'Export', 'Monitoring'])
            ),
            'fr_FR' => sprintf(
                '%s %s %s',
                $this->randomElement(['Parametre', 'Entree', 'Configuration', 'Jeu', 'Reglage']),
                $this->randomElement(['pour', 'avec', 'sans', 'sur']),
                $this->randomElement(['systeme', 'validation', 'import', 'export', 'monitoring'])
            ),
            'es_ES' => sprintf(
                '%s %s %s',
                $this->randomElement(['Parametro', 'Entrada', 'Configuracion', 'Registro', 'Ajuste']),
                $this->randomElement(['para', 'con', 'sin', 'sobre']),
                $this->randomElement(['sistema', 'aprobacion', 'importacion', 'exportacion', 'monitorizacion'])
            ),
            default => sprintf(
                '%s %s %s',
                $this->randomElement(['Setting', 'Entry', 'Record', 'Configuration', 'Parameter']),
                $this->randomElement(['for', 'with', 'without', 'about']),
                $this->randomElement(['system', 'approval', 'import', 'export', 'monitoring'])
            ),
        };
    }

    private function localizedDescription(string $locale): string
    {
        return match ($locale) {
            'de_DE' => sprintf(
                '%s %s',
                $this->randomElement([
                    'Dieser Record speichert Konfigurationen fuer interne Prozesse.',
                    'Dieser Datensatz dokumentiert einen systemweiten Einstellungsstand.',
                    'Dieser Eintrag bildet eine fachliche Vorgabe fuer den Betrieb ab.',
                ]),
                $this->randomElement([
                    'Bitte Werte und Auswirkungen vor Aktivierung pruefen.',
                    'Bitte Aenderungen im Team abstimmen und dokumentieren.',
                    'Bitte gueltige Abhaengigkeiten im Vorfeld verifizieren.',
                ])
            ),
            'fr_FR' => sprintf(
                '%s %s',
                $this->randomElement([
                    'Ce record stocke des configurations pour des processus internes.',
                    'Cet enregistrement documente un etat de parametrage global.',
                    'Cette entree formalise une regle metier pour l exploitation.',
                ]),
                $this->randomElement([
                    'Merci de verifier les valeurs avant activation.',
                    'Merci de valider les changements avec l equipe.',
                    'Merci de confirmer les dependances en amont.',
                ])
            ),
            'es_ES' => sprintf(
                '%s %s',
                $this->randomElement([
                    'Este registro almacena configuraciones para procesos internos.',
                    'Este dato documenta un estado global de parametrizacion.',
                    'Esta entrada define una regla funcional para la operacion.',
                ]),
                $this->randomElement([
                    'Por favor revisa los valores antes de activar.',
                    'Por favor valida los cambios con el equipo.',
                    'Por favor confirma las dependencias previamente.',
                ])
            ),
            default => sprintf(
                '%s %s',
                $this->randomElement([
                    'This record stores configuration values for internal processes.',
                    'This entry documents a global settings state.',
                    'This record defines a business rule for operations.',
                ]),
                $this->randomElement([
                    'Please review values before activation.',
                    'Please align related changes with the team.',
                    'Please verify dependencies beforehand.',
                ])
            ),
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
