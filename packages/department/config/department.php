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
    ],
];
