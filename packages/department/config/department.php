<?php

return [
    'relations' => [
        'contact' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'foreign_key' => 'contact_id',
        ],
    ],
    'navigationgroup' => [
        'label' => 'CRM',
    ],
];
