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
use Moox\Builder\Support\TypedValueColumns;

class BuilderSeeder extends Seeder
{
    public function run(): void
    {
        $this->removeObsoleteGroups();

        $this->seedFahrzeugdatenGroup();
        $this->seedLayoutShowcaseGroup();
        $this->seedDemoValuesForFirstItem();

        app(DefinitionRegistry::class)->forget();
    }

    protected function removeObsoleteGroups(): void
    {
        FieldGroup::query()
            ->whereIn('slug', ['feldtyp-showcase'])
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
            'fields' => [
                [
                    'name' => 'fahrzeugtyp-modell',
                    'label' => 'Fahrzeugtyp / Modell',
                    'type' => 'text',
                    'sort' => 0,
                    'config' => [
                        'placeholder' => 'z. B. Golf GTI',
                        'maxLength' => 120,
                    ],
                ],
                [
                    'name' => 'bruttolistenpreis',
                    'label' => 'Bruttolistenpreis',
                    'type' => 'number',
                    'sort' => 1,
                    'config' => [
                        'suffix' => '€',
                        'min' => 0,
                        'step' => 100,
                    ],
                ],
                [
                    'name' => 'erstzulassung',
                    'label' => 'Erstzulassung',
                    'type' => 'date',
                    'sort' => 2,
                    'config' => [
                        'displayFormat' => 'd.m.Y',
                    ],
                ],
                [
                    'name' => 'motorleistung',
                    'label' => 'Motorleistung',
                    'type' => 'range',
                    'sort' => 3,
                    'config' => [
                        'min' => 50,
                        'max' => 500,
                        'step' => 5,
                        'suffix' => 'PS',
                    ],
                ],
                [
                    'name' => 'getriebe',
                    'label' => 'Getriebe',
                    'type' => 'button_group',
                    'sort' => 4,
                    'config' => [
                        'default' => 'automatic',
                    ],
                    'options' => [
                        ['label' => 'Schaltgetriebe', 'value' => 'manual'],
                        ['label' => 'Automatik', 'value' => 'automatic'],
                    ],
                ],
                [
                    'name' => 'inserat-url',
                    'label' => 'Inserat-Link',
                    'type' => 'link',
                    'sort' => 5,
                ],
                [
                    'name' => 'fahrzeugbeschreibung',
                    'label' => 'Fahrzeugbeschreibung',
                    'type' => 'rich_text',
                    'sort' => 6,
                ],
                [
                    'name' => 'standort',
                    'label' => 'Standort',
                    'type' => 'group',
                    'sort' => 7,
                    'children' => [
                        [
                            'name' => 'stadt',
                            'label' => 'Stadt',
                            'type' => 'text',
                            'sort' => 0,
                            'config' => ['placeholder' => 'z. B. Berlin'],
                        ],
                        [
                            'name' => 'plz',
                            'label' => 'PLZ',
                            'type' => 'text',
                            'sort' => 1,
                            'config' => ['maxLength' => 10],
                        ],
                    ],
                ],
                [
                    'name' => 'ausstattung',
                    'label' => 'Ausstattung',
                    'type' => 'repeater',
                    'sort' => 8,
                    'children' => [
                        [
                            'name' => 'merkmal',
                            'label' => 'Merkmal',
                            'type' => 'text',
                            'sort' => 0,
                            'required' => true,
                        ],
                        [
                            'name' => 'enthalten',
                            'label' => 'Enthalten',
                            'type' => 'toggle',
                            'sort' => 1,
                        ],
                    ],
                ],
            ],
        ]);
    }

    protected function seedLayoutShowcaseGroup(): void
    {
        $group = $this->upsertGroup(
            slug: 'layout-showcase',
            name: 'Layout-Showcase',
            sort: 10,
            entities: ['item', 'record'],
        );

        $this->resetGroupFields($group);

        app(FieldGroupPersistence::class)->sync($group, [
            'name' => 'Layout-Showcase',
            'slug' => 'layout-showcase',
            'active' => true,
            'sort' => 10,
            'target_entities' => ['item', 'record'],
            'fields' => [
                [
                    'name' => 'layout-hinweis',
                    'label' => 'Hinweis',
                    'type' => 'message',
                    'sort' => 0,
                    'config' => [
                        'message' => 'Diese Gruppe demonstriert Tab, Group und Repeater.',
                    ],
                ],
                [
                    'name' => 'tab-allgemein',
                    'label' => 'Allgemein',
                    'type' => 'tab',
                    'sort' => 1,
                ],
                [
                    'name' => 'demo-titel',
                    'label' => 'Titel',
                    'type' => 'text',
                    'sort' => 2,
                    'config' => ['placeholder' => 'Kurzer Titel'],
                ],
                [
                    'name' => 'demo-status',
                    'label' => 'Status',
                    'type' => 'select',
                    'sort' => 3,
                    'options' => [
                        ['label' => 'Entwurf', 'value' => 'draft'],
                        ['label' => 'Veröffentlicht', 'value' => 'published'],
                    ],
                ],
                [
                    'name' => 'tab-details',
                    'label' => 'Details',
                    'type' => 'tab',
                    'sort' => 4,
                ],
                [
                    'name' => 'demo-leistung',
                    'label' => 'Leistung (PS)',
                    'type' => 'number',
                    'sort' => 6,
                    'config' => ['suffix' => 'PS', 'min' => 0],
                ],
                [
                    'name' => 'demo-aktiv',
                    'label' => 'Aktiv',
                    'type' => 'toggle',
                    'sort' => 7,
                ],
                [
                    'name' => 'kontakt',
                    'label' => 'Kontakt',
                    'type' => 'group',
                    'sort' => 8,
                    'children' => [
                        [
                            'name' => 'name',
                            'label' => 'Name',
                            'type' => 'text',
                            'sort' => 0,
                        ],
                        [
                            'name' => 'email',
                            'label' => 'E-Mail',
                            'type' => 'email',
                            'sort' => 1,
                        ],
                    ],
                ],
                [
                    'name' => 'timeline',
                    'label' => 'Timeline',
                    'type' => 'repeater',
                    'sort' => 9,
                    'children' => [
                        [
                            'name' => 'datum',
                            'label' => 'Datum',
                            'type' => 'date',
                            'sort' => 0,
                        ],
                        [
                            'name' => 'ereignis',
                            'label' => 'Ereignis',
                            'type' => 'text',
                            'sort' => 1,
                        ],
                    ],
                ],
                [
                    'name' => 'seiteninhalt',
                    'label' => 'Seiteninhalt',
                    'type' => 'flexible_content',
                    'sort' => 10,
                    'layouts' => [
                        [
                            'name' => 'hero',
                            'label' => 'Hero',
                            'sort' => 0,
                            'children' => [
                                [
                                    'name' => 'titel',
                                    'label' => 'Titel',
                                    'type' => 'text',
                                    'sort' => 0,
                                    'required' => true,
                                ],
                                [
                                    'name' => 'text',
                                    'label' => 'Text',
                                    'type' => 'textarea',
                                    'sort' => 1,
                                ],
                            ],
                        ],
                        [
                            'name' => 'text-block',
                            'label' => 'Textblock',
                            'sort' => 1,
                            'children' => [
                                [
                                    'name' => 'inhalt',
                                    'label' => 'Inhalt',
                                    'type' => 'rich_text',
                                    'sort' => 0,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'demo-oembed',
                    'label' => 'Video (oEmbed)',
                    'type' => 'oembed',
                    'sort' => 11,
                    'config' => [
                        'placeholder' => 'https://www.youtube.com/watch?v=…',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Demo-Werte für Item #1 — nur wenn items-Tabelle und Datensatz existieren.
     */
    protected function seedDemoValuesForFirstItem(): void
    {
        if (! Schema::hasTable('items') || ! DB::table('items')->where('id', 1)->exists()) {
            return;
        }

        $demos = [
            'fahrzeugtyp-modell' => ['type' => 'text', 'value' => 'Golf GTI'],
            'bruttolistenpreis' => ['type' => 'number', 'value' => 32990],
            'erstzulassung' => ['type' => 'date', 'value' => '2022-06-15'],
            'motorleistung' => ['type' => 'range', 'value' => 245],
            'getriebe' => ['type' => 'button_group', 'value' => 'automatic'],
            'inserat-url' => [
                'type' => 'link',
                'value' => [
                    'url' => 'https://moox.org/items/golf-gti',
                    'label' => 'Inserat online ansehen',
                    'target' => '_blank',
                ],
            ],
            'fahrzeugbeschreibung' => [
                'type' => 'rich_text',
                'value' => '<p><strong>Golf GTI</strong> in sehr gutem Zustand.</p>',
            ],
            'standort' => [
                'type' => 'group',
                'value' => [
                    'stadt' => 'Berlin',
                    'plz' => '10115',
                ],
            ],
            'ausstattung' => [
                'type' => 'repeater',
                'value' => [
                    ['merkmal' => 'Sitzheizung', 'enthalten' => true],
                    ['merkmal' => 'Navigationssystem', 'enthalten' => true],
                    ['merkmal' => 'Schiebedach', 'enthalten' => false],
                ],
            ],
            'demo-titel' => ['type' => 'text', 'value' => 'Demo-Eintrag'],
            'demo-status' => ['type' => 'select', 'value' => 'published'],
            'demo-leistung' => ['type' => 'number', 'value' => 245],
            'demo-aktiv' => ['type' => 'toggle', 'value' => true],
            'kontakt' => [
                'type' => 'group',
                'value' => [
                    'name' => 'Max Mustermann',
                    'email' => 'max@moox.org',
                ],
            ],
            'timeline' => [
                'type' => 'repeater',
                'value' => [
                    ['datum' => '2022-06-15', 'ereignis' => 'Erstzulassung'],
                    ['datum' => '2024-03-01', 'ereignis' => 'Inspektion'],
                ],
            ],
            'seiteninhalt' => [
                'type' => 'flexible_content',
                'value' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'titel' => 'Willkommen beim Layout-Showcase',
                            'text' => 'Flexible Inhalte mit unterschiedlichen Layouts.',
                        ],
                    ],
                    [
                        'type' => 'text-block',
                        'data' => [
                            'inhalt' => '<p>Dies ist ein <strong>Textblock</strong> im Flexible-Content-Feld.</p>',
                        ],
                    ],
                ],
            ],
            'demo-oembed' => [
                'type' => 'oembed',
                'value' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
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
