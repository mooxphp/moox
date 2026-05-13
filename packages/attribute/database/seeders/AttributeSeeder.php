<?php

namespace Moox\Attribute\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Moox\Attribute\Models\Attribute;
use Moox\Attribute\Models\AttributeTranslation;
use Moox\Attribute\Models\AttributeValues;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            [
                'type' => 'text',
                'name' => 'Color',
                'description' => 'A color attribute.',
                'translation' => ['value' => ['label' => 'Color']],
                'values' => [
                    ['value' => ['key' => 'red', 'label' => 'Red']],
                    ['value' => ['key' => 'blue', 'label' => 'Blue']],
                ],
            ],
            [
                'type' => 'boolean',
                'name' => 'Featured',
                'description' => 'Whether something is featured.',
                'translation' => ['value' => ['label' => 'Featured']],
                'values' => [
                    ['value' => ['key' => true, 'label' => 'Yes']],
                    ['value' => ['key' => false, 'label' => 'No']],
                ],
            ],
        ];

        foreach ($attributes as $data) {
            /** @var Attribute $attribute */
            $attribute = Attribute::query()->updateOrCreate(
                ['name' => $data['name']],
                [
                    'type' => $data['type'],
                    'description' => $data['description'],
                    'status' => 'draft',
                    'uuid' => (string) Str::uuid(),
                    'ulid' => (string) Str::ulid(),
                ],
            );

            AttributeTranslation::query()->updateOrCreate(
                [
                    'attribute_id' => $attribute->getKey(),
                    'locale' => 'en_US',
                ],
                [
                    'translation_status' => 'draft',
                    'value' => $data['translation']['value'],
                ],
            );

            foreach ($data['values'] as $valueData) {
                AttributeValues::query()->updateOrCreate(
                    [
                        'attribute_id' => $attribute->getKey(),
                        'value' => $valueData['value'],
                    ],
                    [],
                );
            }
        }
    }
}

