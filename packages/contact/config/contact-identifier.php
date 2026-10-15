<?php

return [
    'relations' => [
        'contact' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Contact\\Models\\Contact',
            'foreign_key' => 'contact_id',
            'title_attribute' => 'display_name',
        ],
        'contactIdentifierType' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Contact\\Models\\ContactIdentifierType',
            'foreign_key' => 'contact_identifier_type_id',
            'title_attribute' => 'title',
        ],
        'country' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Data\\Models\\StaticCountry',
            'foreign_key' => 'country_id',
            'title_attribute' => 'common_name',
        ],
    ],
];
