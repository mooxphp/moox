<?php

return [
    'relations' => [
        'communicationType' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Communication\\Models\\CommunicationType',
            'foreign_key' => 'communication_type_id',
            'title_attribute' => 'title',
        ],
    ],
];
