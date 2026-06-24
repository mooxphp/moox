<?php

declare(strict_types=1);

namespace Moox\Builder\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Services\TabStructureMigrator;
use Moox\Builder\Support\TypedValueColumns;

class BuilderSeeder extends Seeder
{
    public function run(): void
    {
        $this->removeObsoleteGroups();
        $this->migrateLegacyTabStructures();

        $this->seedFahrzeugdatenGroup();
        $this->seedDemoValuesForFirstItem();

        app(DefinitionRegistry::class)->forget();
    }

    protected function migrateLegacyTabStructures(): void
    {
        $migrator = app(TabStructureMigrator::class);

        FieldGroup::query()->each(function (FieldGroup $group) use ($migrator): void {
            $migrator->migrateGroup($group);
        });
    }

    protected function removeObsoleteGroups(): void
    {
        FieldGroup::query()
            ->whereIn('slug', ['feldtyp-showcase', 'layout-showcase'])
            ->each(fn (FieldGroup $group) => $group->delete());
    }

    protected function seedFahrzeugdatenGroup(): void
    {
        $group = $this->upsertGroup(
            slug: 'fahrzeugdaten',
            name: 'Fahrzeugdaten',
            sort: 0,
            entities: ['item'],
        );

        $this->resetGroupFields($group);

        app(FieldGroupPersistence::class)->sync($group, [
            'name' => 'Fahrzeugdaten',
            'slug' => 'fahrzeugdaten',
            'active' => true,
            'sort' => 0,
            'target_entities' => ['item'],
            'fields' => $this->fahrzeugdatenFields(),
        ]);
    }

    /**
     * Alle wählbaren Feldtypen am Beispiel eines Fahrzeuginserats.
     *
     * @return list<array<string, mixed>>
     */
    protected function fahrzeugdatenFields(): array
    {
        return [
            [
                'name' => 'hinweis',
                'label' => 'Hinweis',
                'type' => 'message',
                'sort' => 0,
                'config' => [
                    'message' => 'Stammdaten stehen direkt in der Section. Weitere Feldtypen findest du in den Tabs „Technik & Ausstattung“ und „Inserat & Medien“.',
                ],
            ],
            $this->field('fahrzeugtyp-modell', 'Fahrzeugtyp / Modell', 'text', 1, required: true, config: [
                'placeholder' => 'z. B. Golf GTI',
                'maxLength' => 120,
                'default' => 'Golf GTI',
            ]),
            $this->field('kurzbeschreibung', 'Kurzbeschreibung', 'textarea', 2, config: [
                'rows' => 3,
                'placeholder' => 'Einzeiler für Listenansicht …',
            ]),
            $this->field('karosserieform', 'Karosserieform', 'select', 3, config: ['default' => 'limousine'], options: [
                ['label' => 'Limousine', 'value' => 'limousine'],
                ['label' => 'Kombi', 'value' => 'kombi'],
                ['label' => 'SUV', 'value' => 'suv'],
                ['label' => 'Coupé', 'value' => 'coupe'],
            ]),
            $this->field('bruttolistenpreis', 'Bruttolistenpreis', 'number', 4, config: [
                'suffix' => '€', 'min' => 0, 'step' => 100, 'default' => 32990,
            ]),
            $this->field('motorleistung', 'Motorleistung', 'range', 5, config: [
                'min' => 50, 'max' => 500, 'step' => 5, 'suffix' => 'PS', 'default' => 245,
            ]),
            $this->field('erstzulassung', 'Erstzulassung', 'date', 6, config: [
                'displayFormat' => 'd.m.Y', 'default' => '2022-06-15',
            ]),
            $this->field('kraftstoff', 'Kraftstoff', 'radio', 7, config: ['default' => 'benzin'], options: [
                ['label' => 'Benzin', 'value' => 'benzin'],
                ['label' => 'Diesel', 'value' => 'diesel'],
                ['label' => 'Elektro', 'value' => 'elektro'],
                ['label' => 'Hybrid', 'value' => 'hybrid'],
            ]),
            $this->field('getriebe', 'Getriebe', 'button_group', 8, config: ['default' => 'automatic'], options: [
                ['label' => 'Schaltgetriebe', 'value' => 'manual'],
                ['label' => 'Automatik', 'value' => 'automatic'],
            ]),
            $this->field('lackfarbe', 'Lackfarbe', 'color', 9, config: ['default' => '#1a1a1a']),
            $this->field('unfallfrei', 'Unfallfrei', 'toggle', 10, config: ['default' => true]),
            [
                'name' => 'standort',
                'label' => 'Standort',
                'type' => 'group',
                'sort' => 11,
                'children' => [
                    $this->field('stadt', 'Stadt', 'text', 0, config: ['placeholder' => 'z. B. Berlin']),
                    $this->field('plz', 'PLZ', 'text', 1, config: ['maxLength' => 10]),
                ],
            ],
            $this->field('haendler-email', 'Händler-E-Mail', 'email', 12, config: [
                'placeholder' => 'verkauf@autohaus.de',
            ]),
            $this->field('fahrzeugbeschreibung', 'Fahrzeugbeschreibung', 'rich_text', 13),
            $this->field('inserat-url', 'Inserat-Link', 'link', 14),
            $this->tabField('tab-technik', 'Technik & Ausstattung', 15, [
                $this->field('letzte-wartung', 'Letzte Wartung', 'datetime', 0, config: [
                    'displayFormat' => 'd.m.Y H:i', 'default' => '2024-03-01 09:30',
                ]),
                $this->field('besichtigungszeit', 'Bevorzugte Besichtigungszeit', 'time', 1, config: [
                    'default' => '14:00',
                ]),
                $this->field('zahlungsoptionen', 'Zahlungsoptionen', 'multiselect', 2, options: [
                    ['label' => 'Barzahlung', 'value' => 'cash'],
                    ['label' => 'Finanzierung', 'value' => 'finance'],
                    ['label' => 'Leasing', 'value' => 'leasing'],
                    ['label' => 'Inzahlungnahme', 'value' => 'trade_in'],
                ]),
                $this->field('serienausstattung', 'Serienausstattung', 'checkbox_list', 3, options: [
                    ['label' => 'Klimaautomatik', 'value' => 'climate'],
                    ['label' => 'Tempomat', 'value' => 'cruise'],
                    ['label' => 'Einparkhilfe', 'value' => 'parking'],
                    ['label' => 'LED-Scheinwerfer', 'value' => 'led'],
                ]),
                [
                    'name' => 'ausstattung',
                    'label' => 'Zusatzausstattung',
                    'type' => 'repeater',
                    'sort' => 4,
                    'config' => ['min' => 1, 'max' => 20],
                    'children' => [
                        $this->field('merkmal', 'Merkmal', 'text', 0, required: true),
                        $this->field('enthalten', 'Enthalten', 'toggle', 1, config: ['default' => true]),
                    ],
                ],
                [
                    'name' => 'wartungshistorie',
                    'label' => 'Wartungshistorie',
                    'type' => 'repeater',
                    'sort' => 5,
                    'children' => [
                        $this->field('datum', 'Datum', 'date', 0),
                        $this->field('beschreibung', 'Beschreibung', 'text', 1),
                    ],
                ],
            ]),
            $this->tabField('tab-inserat', 'Inserat & Medien', 16, [
                $this->field('hersteller-seite', 'Herstellerseite', 'url', 0, config: [
                    'placeholder' => 'https://www.volkswagen.de',
                ]),
                $this->field('interner-zugang', 'Interner Zugangscode', 'password', 1),
                $this->field('fahrzeugvideo', 'Fahrzeugvideo (oEmbed)', 'oembed', 2, config: [
                    'placeholder' => 'https://www.youtube.com/watch?v=…',
                ]),
                [
                    'name' => 'inserat-inhalt',
                    'label' => 'Inserat-Inhalt (Flexible Inhalte)',
                    'type' => 'flexible_content',
                    'sort' => 3,
                    'config' => ['min' => 1, 'max' => 10],
                    'layouts' => [
                        [
                            'name' => 'hero',
                            'label' => 'Hero-Bereich',
                            'sort' => 0,
                            'children' => [
                                $this->field('titel', 'Titel', 'text', 0, required: true),
                                $this->field('text', 'Text', 'textarea', 1),
                            ],
                        ],
                        [
                            'name' => 'technik-block',
                            'label' => 'Technik-Block',
                            'sort' => 1,
                            'children' => [
                                $this->field('ueberschrift', 'Überschrift', 'text', 0),
                                $this->field('details', 'Details', 'rich_text', 1),
                            ],
                        ],
                    ],
                ],
            ]),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    protected function tabField(string $name, string $label, int $sort, array $children): array
    {
        return [
            'name' => $name,
            'label' => $label,
            'type' => 'tab',
            'sort' => $sort,
            'children' => $children,
        ];
    }

    /**
     * @param  list<array{label: string, value: string}>  $options
     * @return array<string, mixed>
     */
    protected function field(
        string $name,
        string $label,
        string $type,
        int $sort,
        bool $required = false,
        array $config = [],
        array $options = [],
    ): array {
        $field = [
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'sort' => $sort,
            'required' => $required,
        ];

        if ($config !== []) {
            $field['config'] = $config;
        }

        if ($options !== []) {
            $field['options'] = $options;
        }

        return $field;
    }

    /**
     * Demo-Werte für Item #1 — nur wenn items-Tabelle und Datensatz existieren.
     */
    protected function seedDemoValuesForFirstItem(): void
    {
        if (! Schema::hasTable('items') || ! DB::table('items')->where('id', 1)->exists()) {
            return;
        }

        FieldValue::query()
            ->where('entity', 'item')
            ->where('record_id', 1)
            ->delete();

        $demos = [
            'fahrzeugtyp-modell' => ['type' => 'text', 'value' => 'Golf GTI'],
            'kurzbeschreibung' => [
                'type' => 'textarea',
                'value' => 'Sportlicher Kompaktwagen mit 245 PS, unfallfrei, scheckheftgepflegt.',
            ],
            'karosserieform' => ['type' => 'select', 'value' => 'limousine'],
            'bruttolistenpreis' => ['type' => 'number', 'value' => 32990],
            'motorleistung' => ['type' => 'range', 'value' => 245],
            'lackfarbe' => ['type' => 'color', 'value' => '#1a1a1a'],
            'unfallfrei' => ['type' => 'toggle', 'value' => true],
            'kraftstoff' => ['type' => 'radio', 'value' => 'benzin'],
            'getriebe' => ['type' => 'button_group', 'value' => 'automatic'],
            'erstzulassung' => ['type' => 'date', 'value' => '2022-06-15'],
            'letzte-wartung' => ['type' => 'datetime', 'value' => '2024-03-01 09:30:00'],
            'besichtigungszeit' => ['type' => 'time', 'value' => '14:00'],
            'zahlungsoptionen' => [
                'type' => 'multiselect',
                'value' => ['finance', 'trade_in'],
            ],
            'serienausstattung' => [
                'type' => 'checkbox_list',
                'value' => ['climate', 'cruise', 'led'],
            ],
            'standort' => [
                'type' => 'group',
                'value' => [
                    'stadt' => 'Berlin',
                    'plz' => '10115',
                ],
            ],
            'haendler-email' => ['type' => 'email', 'value' => 'verkauf@moox-autohaus.de'],
            'hersteller-seite' => ['type' => 'url', 'value' => 'https://www.volkswagen.de'],
            'interner-zugang' => ['type' => 'password', 'value' => 'demo-geheim-2024'],
            'ausstattung' => [
                'type' => 'repeater',
                'value' => [
                    ['merkmal' => 'Sitzheizung vorn', 'enthalten' => true],
                    ['merkmal' => 'Navigationssystem Discover Pro', 'enthalten' => true],
                    ['merkmal' => 'Schiebedach', 'enthalten' => false],
                    ['merkmal' => 'Sportsitze', 'enthalten' => true],
                ],
            ],
            'wartungshistorie' => [
                'type' => 'repeater',
                'value' => [
                    ['datum' => '2022-06-15', 'beschreibung' => 'Erstzulassung'],
                    ['datum' => '2023-06-10', 'beschreibung' => 'Inspektion beim Vertragshändler'],
                    ['datum' => '2024-03-01', 'beschreibung' => 'Ölwechsel und Bremsen geprüft'],
                ],
            ],
            'fahrzeugbeschreibung' => [
                'type' => 'rich_text',
                'value' => '<p><strong>VW Golf GTI (245 PS)</strong> in sehr gutem Zustand.</p>'
                    .'<p>Scheckheftgepflegt, Nichtraucherfahrzeug, sofort verfügbar.</p>',
            ],
            'inserat-url' => [
                'type' => 'link',
                'value' => [
                    'url' => 'https://moox.org/items/golf-gti',
                    'label' => 'Inserat online ansehen',
                    'target' => '_blank',
                ],
            ],
            'fahrzeugvideo' => [
                'type' => 'oembed',
                'value' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
            'inserat-inhalt' => [
                'type' => 'flexible_content',
                'value' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'titel' => 'Golf GTI — sofort verfügbar',
                            'text' => '245 PS, Automatik, unfallfrei. Jetzt Probefahrt vereinbaren.',
                        ],
                    ],
                    [
                        'type' => 'technik-block',
                        'data' => [
                            'ueberschrift' => 'Technische Daten',
                            'details' => '<ul><li>2,0 l TSI, 245 PS</li><li>7-Gang DSG</li><li>Verbrauch komb.: 6,9 l/100 km</li></ul>',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($demos as $fieldName => $demo) {
            $this->upsertFieldValue('item', 1, $fieldName, $demo['type'], $demo['value']);
        }
    }

    /**
     * @param  list<string>  $entities
     */
    protected function upsertGroup(string $slug, string $name, int $sort, array $entities): FieldGroup
    {
        $locationRules = array_map(
            fn (string $entity): array => [[
                'param' => 'entity',
                'operator' => '==',
                'value' => $entity,
            ]],
            $entities,
        );

        return FieldGroup::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'location_rules' => $locationRules,
                'placement' => 'default',
                'sort' => $sort,
                'active' => true,
            ],
        );
    }

    protected function resetGroupFields(FieldGroup $group): void
    {
        $group->fields()->each(fn (Field $field) => $field->delete());
    }

    protected function upsertFieldValue(string $entity, int $recordId, string $fieldName, string $type, mixed $value): void
    {
        FieldValue::query()->updateOrCreate(
            [
                'entity' => $entity,
                'record_id' => $recordId,
                'field_name' => $fieldName,
            ],
            TypedValueColumns::attributesFor($type, $value),
        );
    }
}
