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
        'language' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Data\\Models\\StaticLanguage',
            'foreign_key' => 'language_id',
            'title_attribute' => 'common_name',
        ],
    ],
];
