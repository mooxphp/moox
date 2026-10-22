<?php

return [
    'relations' => [
        'address' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Address\\Models\\Address',
            'foreign_key' => 'address_id',
            'title_attribute' => 'street',
        ],
        'addressUsageType' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Address\\Models\\AddressUsageType',
            'foreign_key' => 'address_usage_type_id',
            'title_attribute' => 'title',
        ],
    ],
    'navigationgroup' => [
        'label' => 'CRM',
    ],
];
