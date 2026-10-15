<?php

return [
    'relations' => [
        'contact' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'foreign_key' => 'contact_id',
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
