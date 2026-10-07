<?php

return [
    'relations' => [
        'language' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Data\\Models\\StaticLanguage',
            'foreign_key' => 'language_id',
            'title_attribute' => 'common_name',
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
