<?php

return [
    'relations' => [
        'salutation' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Salutation\\Models\\Salutation',
            'foreign_key' => 'salutation_id',
            'title_attribute' => 'title',
        ],
    ],
];
