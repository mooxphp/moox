<?php

return [
    'owner_types' => [],
    'relations' => [
        'country' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Data\\Models\\StaticCountry',
            'foreign_key' => 'country_id',
            'title_attribute' => 'common_name',
        ],
        'usages' => [
            'kind' => 'has_many',
            'presentation' => 'tab',
            'label' => 'trans//address::address.address_usages',
            'relationship' => 'usages',
            'inverse_relationship' => 'address',
            'model' => 'Moox\\Address\\Models\\AddressUsage',
            'related_resource' => 'Moox\\Address\\Resources\\AddressUsageResource',
            'foreign_key' => 'address_id',
            'translation_prefix' => 'address::fields',
            'display_columns' => ['address_usage_type_id'],
            'create_prefill' => [
                'address_id' => 'owner.id',
            ],
        ],
    ],
    'navigationgroup' => [
        'label' => 'CRM',
    ],
];
