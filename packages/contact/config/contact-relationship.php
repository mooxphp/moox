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
        'relatedContact' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Contact\\Models\\Contact',
            'foreign_key' => 'related_contact_id',
            'title_attribute' => 'display_name',
        ],
        'relationshipType' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Contact\\Models\\ContactRelationshipType',
            'foreign_key' => 'relationship_type_id',
            'title_attribute' => 'title',
        ],
    ],
];
