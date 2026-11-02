<?php

return [
    'relations' => [
        'contact' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'foreign_key' => 'contact_id',
        ],
        'salutation' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'foreign_key' => 'salutation_id',
        ],
        'honorificTitle' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'foreign_key' => 'honorific_title_id',
        ],
        'nationality' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Data\\Models\\StaticCountry',
            'foreign_key' => 'nationality_id',
            'title_attribute' => 'common_name',
        ],
        'language' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Data\\Models\\StaticLanguage',
            'foreign_key' => 'language_id',
            'title_attribute' => 'common_name',
        ],
    ],
    'navigationgroup' => [
        'label' => 'CRM',
    ],
];
