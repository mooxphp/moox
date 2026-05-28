<?php

declare(strict_types=1);

namespace Moox\Item\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Moox\Item\Models\Item;

class ItemSeeder extends Seeder
{
    public const DEMO_TITLE_PREFIX = 'Demo Item';

    public const DEFAULT_ITEM_COUNT = 100;

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
        $this->purgeDemoItems();

        $faker = fake();
        $count = $this->resolveItemCount();
        $created = 0;

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
            $this->reportCreated("Item {$item->getKey()}");
        }

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

    private function resolveItemCount(): int
    {
        if (class_exists(\Moox\Demo\Seeding\SeedingConfig::class)) {
            return \Moox\Demo\Seeding\SeedingConfig::resolveCount('item', self::DEFAULT_ITEM_COUNT);
        }

        return self::DEFAULT_ITEM_COUNT;
    }

    private function localizedTitle(string $locale): string
    {
        return match ($locale) {
            'de_DE' => self::DEMO_TITLE_PREFIX.' '.$this->randomElement([
                'Einstellung',
                'Vorlage',
                'Konfiguration',
                'Baustein',
                'Eintrag',
            ]),
            'fr_FR' => self::DEMO_TITLE_PREFIX.' '.$this->randomElement([
                'Parametre',
                'Modele',
                'Configuration',
                'Bloc',
                'Entree',
            ]),
            'es_ES' => self::DEMO_TITLE_PREFIX.' '.$this->randomElement([
                'Parametro',
                'Plantilla',
                'Configuracion',
                'Bloque',
                'Entrada',
            ]),
            default => self::DEMO_TITLE_PREFIX.' '.$this->randomElement([
                'Setting',
                'Template',
                'Configuration',
                'Block',
                'Entry',
            ]),
        };
    }

    private function localizedDescription(string $locale): string
    {
        return match ($locale) {
            'de_DE' => $this->randomElement([
                'Dieser Demo-Item-Eintrag dient als Beispiel fuer strukturierte Inhalte.',
                'Dieser Datensatz kann als Vorlage fuer eigene Item-Objekte genutzt werden.',
                'Dieser Eintrag simuliert reale Daten fuer Tests und Demos.',
            ]),
            'fr_FR' => $this->randomElement([
                'Cet element de demonstration sert d exemple pour des contenus structures.',
                'Cet enregistrement peut etre utilise comme modele pour vos propres elements.',
                'Cette entree simule des donnees reelles pour les tests et demos.',
            ]),
            'es_ES' => $this->randomElement([
                'Este item de demostracion sirve como ejemplo de contenido estructurado.',
                'Este registro puede usarse como plantilla para tus propios items.',
                'Esta entrada simula datos reales para pruebas y demos.',
            ]),
            default => $this->randomElement([
                'This demo item serves as an example for structured content.',
                'This record can be used as a template for your own item entities.',
                'This entry simulates real data for tests and demos.',
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
