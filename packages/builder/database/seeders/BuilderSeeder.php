<?php

declare(strict_types=1);

namespace Moox\Builder\Database\Seeders;

use Illuminate\Database\Seeder;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldOption;
use Moox\Builder\Registry\DefinitionRegistry;

class BuilderSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFahrzeugdatenGroup();

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
            ],
        ]);

        $this->upsertField($group, [
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
        ]);

        $this->upsertField($group, [
            'name' => 'inserat-url',
            'label' => 'Inserat-Link',
            'type' => 'link',
            'sort' => 5,
        ]);

        $this->upsertField($group, [
            'name' => 'fahrzeugbeschreibung',
            'label' => 'Fahrzeugbeschreibung',
            'type' => 'rich_text',
            'sort' => 6,
        ]);
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
}
