<?php

return [
    'relations' => [
        'department' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Department\\Models\\Department',
            'foreign_key' => 'department_id',
            'title_attribute' => 'title',
        ],
        'person' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Person\\Models\\Person',
            'foreign_key' => 'person_id',
            'title_attribute' => 'last_name',
        ],
    ],
    'navigationgroup' => [
        'label' => 'CRM',
    ],
];
