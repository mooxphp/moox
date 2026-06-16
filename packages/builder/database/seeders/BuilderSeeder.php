<?php

declare(strict_types=1);

namespace Moox\Builder\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldOption;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Support\TypedValueColumns;

class BuilderSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFahrzeugdatenGroup();
        $this->seedFieldTypeShowcaseGroup();
        $this->seedDemoValuesForFirstItem();

        app(DefinitionRegistry::class)->forget();
    }

    protected function seedFahrzeugdatenGroup(): void
    {
        $group = $this->upsertGroup(
            slug: 'fahrzeugdaten',
            name: 'Fahrzeugdaten',
            sort: 0,
        );

        $this->upsertField($group, [
            'name' => 'fahrzeugtyp-modell',
            'label' => 'Fahrzeugtyp / Modell',
            'type' => 'text',
            'sort' => 0,
            'config' => [
                'placeholder' => 'z. B. Golf GTI',
                'maxLength' => 120,
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'bruttolistenpreis',
            'label' => 'Bruttolistenpreis',
            'type' => 'number',
            'sort' => 1,
            'config' => [
                'suffix' => '€',
                'min' => 0,
                'step' => 100,
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'erstzulassung',
            'label' => 'Erstzulassung',
            'type' => 'date',
            'sort' => 2,
            'config' => [
                'displayFormat' => 'd.m.Y',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'motorleistung',
            'label' => 'Motorleistung',
            'type' => 'range',
            'sort' => 3,
            'config' => [
                'min' => 50,
                'max' => 500,
                'step' => 5,
                'suffix' => 'PS',
                'helperText' => 'value_decimal — Schieberegler für Leistung',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'getriebe',
            'label' => 'Getriebe',
            'type' => 'button_group',
            'sort' => 4,
            'config' => [
                'default' => 'automatic',
                'helperText' => 'value_string — Button-Gruppe',
            ],
            'options' => [
                ['label' => 'Schaltgetriebe', 'value' => 'manual'],
                ['label' => 'Automatik', 'value' => 'automatic'],
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'inserat-url',
            'label' => 'Inserat-Link',
            'type' => 'link',
            'sort' => 5,
            'config' => [
                'helperText' => 'value_json — URL, Bezeichnung und Ziel',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'fahrzeugbeschreibung',
            'label' => 'Fahrzeugbeschreibung',
            'type' => 'rich_text',
            'sort' => 6,
            'config' => [
                'helperText' => 'value_text — formatierter Langtext',
            ],
        ]);
    }

    protected function seedFieldTypeShowcaseGroup(): void
    {
        $group = $this->upsertGroup(
            slug: 'feldtyp-showcase',
            name: 'Feldtyp-Showcase (alle 19 Typen)',
            sort: 10,
        );

        $this->upsertField($group, [
            'name' => 'demo-text',
            'label' => 'Text (kurz)',
            'type' => 'text',
            'sort' => 0,
            'required' => true,
            'config' => [
                'placeholder' => 'Kurzer Text …',
                'prefix' => 'REF-',
                'maxLength' => 50,
                'default' => 'Standardtext',
                'helperText' => 'value_string in der Datenbank',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-textarea',
            'label' => 'Text (mehrzeilig)',
            'type' => 'textarea',
            'sort' => 1,
            'config' => [
                'rows' => 4,
                'placeholder' => 'Mehrzeilige Beschreibung …',
                'helperText' => 'value_text in der Datenbank',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-number',
            'label' => 'Zahl',
            'type' => 'number',
            'sort' => 2,
            'config' => [
                'prefix' => '€',
                'suffix' => 'brutto',
                'min' => 0,
                'max' => 500000,
                'step' => 0.01,
                'placeholder' => '0,00',
                'helperText' => 'value_decimal in der Datenbank',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-email',
            'label' => 'E-Mail',
            'type' => 'email',
            'sort' => 3,
            'config' => [
                'placeholder' => 'name@beispiel.de',
                'helperText' => 'value_string + email-Validierung',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-url',
            'label' => 'URL',
            'type' => 'url',
            'sort' => 4,
            'config' => [
                'placeholder' => 'https://moox.org',
                'helperText' => 'value_string + url-Validierung',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-password',
            'label' => 'Passwort',
            'type' => 'password',
            'sort' => 5,
            'config' => [
                'maxLength' => 64,
                'helperText' => 'Wird nur gespeichert wenn ausgefüllt (value_string)',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-select',
            'label' => 'Auswahl (Dropdown)',
            'type' => 'select',
            'sort' => 6,
            'config' => [
                'default' => 'bmw',
                'helperText' => 'value_string — gewählter Options-Wert',
            ],
            'options' => [
                ['label' => 'BMW', 'value' => 'bmw'],
                ['label' => 'Audi', 'value' => 'audi'],
                ['label' => 'Mercedes', 'value' => 'mercedes'],
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-multiselect',
            'label' => 'Mehrfachauswahl',
            'type' => 'multiselect',
            'sort' => 7,
            'config' => [
                'helperText' => 'value_json — Array der Options-Werte',
            ],
            'options' => [
                ['label' => 'Sportpaket', 'value' => 'sport'],
                ['label' => 'Komfortpaket', 'value' => 'comfort'],
                ['label' => 'Assistenzpaket', 'value' => 'assist'],
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-checkbox-list',
            'label' => 'Checkbox-Liste',
            'type' => 'checkbox_list',
            'sort' => 8,
            'required' => true,
            'config' => [
                'helperText' => 'value_json — Array der angehakten Options-Werte',
            ],
            'options' => [
                ['label' => 'LED-Scheinwerfer', 'value' => 'led'],
                ['label' => 'Navigationssystem', 'value' => 'navi'],
                ['label' => 'Sitzheizung', 'value' => 'heating'],
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-radio',
            'label' => 'Optionsfelder (Radio)',
            'type' => 'radio',
            'sort' => 9,
            'config' => [
                'helperText' => 'value_string — ein Options-Wert',
            ],
            'options' => [
                ['label' => 'Benzin', 'value' => 'petrol'],
                ['label' => 'Diesel', 'value' => 'diesel'],
                ['label' => 'Elektro', 'value' => 'electric'],
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-toggle',
            'label' => 'Schalter',
            'type' => 'toggle',
            'sort' => 10,
            'config' => [
                'default' => '1',
                'helperText' => 'value_boolean in der Datenbank',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-date',
            'label' => 'Datum',
            'type' => 'date',
            'sort' => 11,
            'config' => [
                'displayFormat' => 'd.m.Y',
                'helperText' => 'value_date in der Datenbank',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-datetime',
            'label' => 'Datum & Uhrzeit',
            'type' => 'datetime',
            'sort' => 12,
            'config' => [
                'displayFormat' => 'd.m.Y H:i',
                'helperText' => 'value_datetime in der Datenbank',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-time',
            'label' => 'Uhrzeit',
            'type' => 'time',
            'sort' => 13,
            'config' => [
                'default' => '09:00',
                'helperText' => 'value_string (TimePicker)',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-color',
            'label' => 'Farbe',
            'type' => 'color',
            'sort' => 14,
            'config' => [
                'default' => '#3b82f6',
                'helperText' => 'value_string — Hex-Farbwert',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-range',
            'label' => 'Bereich (Slider)',
            'type' => 'range',
            'sort' => 15,
            'config' => [
                'min' => 0,
                'max' => 100,
                'step' => 5,
                'default' => '50',
                'helperText' => 'value_decimal — Schieberegler',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-button-group',
            'label' => 'Button-Gruppe',
            'type' => 'button_group',
            'sort' => 16,
            'config' => [
                'default' => 'used',
                'helperText' => 'value_string — Toggle-Buttons',
            ],
            'options' => [
                ['label' => 'Neuwagen', 'value' => 'new'],
                ['label' => 'Gebraucht', 'value' => 'used'],
                ['label' => 'Vorführwagen', 'value' => 'demo'],
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-link',
            'label' => 'Link',
            'type' => 'link',
            'sort' => 17,
            'config' => [
                'helperText' => 'value_json — URL, Bezeichnung und Ziel',
            ],
        ]);

        $this->upsertField($group, [
            'name' => 'demo-rich-text',
            'label' => 'Rich Text',
            'type' => 'rich_text',
            'sort' => 18,
            'config' => [
                'helperText' => 'value_text — formatierter HTML-Text',
            ],
        ]);
    }

    /**
     * Demo-Werte für Item #1 — nur wenn die items-Tabelle und Datensatz 1 existieren.
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
                'value' => '<p><strong>Golf GTI</strong> in sehr gutem Zustand mit <em>vollständiger Ausstattung</em>.</p><ul><li>LED-Scheinwerfer</li><li>Navigationssystem</li><li>Sitzheizung</li></ul>',
            ],
            'demo-text' => ['type' => 'text', 'value' => 'REF-DEMO-001'],
            'demo-textarea' => ['type' => 'textarea', 'value' => "Zeile 1: Ausstattung\nZeile 2: Zustand sehr gut\nZeile 3: Scheckheftgepflegt"],
            'demo-number' => ['type' => 'number', 'value' => 28990.50],
            'demo-email' => ['type' => 'email', 'value' => 'demo@moox.org'],
            'demo-url' => ['type' => 'url', 'value' => 'https://moox.org'],
            'demo-select' => ['type' => 'select', 'value' => 'audi'],
            'demo-multiselect' => ['type' => 'multiselect', 'value' => ['sport', 'comfort']],
            'demo-checkbox-list' => ['type' => 'checkbox_list', 'value' => ['led', 'navi']],
            'demo-radio' => ['type' => 'radio', 'value' => 'diesel'],
            'demo-toggle' => ['type' => 'toggle', 'value' => true],
            'demo-date' => ['type' => 'date', 'value' => '2024-03-15'],
            'demo-datetime' => ['type' => 'datetime', 'value' => '2024-03-15 14:30:00'],
            'demo-time' => ['type' => 'time', 'value' => '14:30'],
            'demo-color' => ['type' => 'color', 'value' => '#22c55e'],
            'demo-range' => ['type' => 'range', 'value' => 75],
            'demo-button-group' => ['type' => 'button_group', 'value' => 'used'],
            'demo-link' => [
                'type' => 'link',
                'value' => [
                    'url' => 'https://moox.org',
                    'label' => 'Mehr auf moox.org',
                    'target' => '_blank',
                ],
            ],
            'demo-rich-text' => [
                'type' => 'rich_text',
                'value' => '<p>Dies ist ein <strong>formatierter</strong> Demo-Text mit <em>Rich-Text-Editor</em>.</p>',
            ],
        ];

        foreach ($demos as $fieldName => $demo) {
            $this->upsertFieldValue('item', 1, $fieldName, $demo['type'], $demo['value']);
        }
    }

    protected function upsertGroup(string $slug, string $name, int $sort): FieldGroup
    {
        return FieldGroup::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'location_rules' => [
                    [
                        [
                            'param' => 'entity',
                            'operator' => '==',
                            'value' => 'item',
                        ],
                    ],
                ],
                'placement' => 'default',
                'sort' => $sort,
                'active' => true,
            ],
        );
    }

    /**
     * @param  array{
     *     name: string,
     *     label: string,
     *     type: string,
     *     sort: int,
     *     config?: array<string, mixed>,
     *     required?: bool,
     *     options?: list<array{label: string, value: string}>
     * }  $attributes
     */
    protected function upsertField(FieldGroup $group, array $attributes): Field
    {
        $field = Field::query()->updateOrCreate(
            [
                'field_group_id' => $group->getKey(),
                'name' => $attributes['name'],
            ],
            [
                'label' => $attributes['label'],
                'type' => $attributes['type'],
                'config' => $attributes['config'] ?? [],
                'validation' => [
                    'required' => (bool) ($attributes['required'] ?? false),
                    'rules' => [],
                ],
                'sort' => $attributes['sort'],
            ],
        );

        if (isset($attributes['options'])) {
            $this->syncFieldOptions($field, $attributes['options']);
        } else {
            $field->options()->delete();
        }

        return $field;
    }

    /**
     * @param  list<array{label: string, value: string}>  $options
     */
    protected function syncFieldOptions(Field $field, array $options): void
    {
        $retainedIds = [];

        foreach ($options as $index => $option) {
            $model = FieldOption::query()->updateOrCreate(
                [
                    'field_id' => $field->getKey(),
                    'value' => $option['value'],
                ],
                [
                    'label' => $option['label'],
                    'sort' => $index,
                ],
            );

            $retainedIds[] = $model->getKey();
        }

        $field->options()
            ->whereNotIn('id', $retainedIds)
            ->delete();
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
