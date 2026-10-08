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
        'organizationType' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Organization\\Models\\OrganizationType',
            'foreign_key' => 'organization_type_id',
            'title_attribute' => 'title',
        ],
        'legalForm' => [
            'kind' => 'belongs_to',
            'presentation' => 'hidden',
            'model' => 'Moox\\Organization\\Models\\LegalForm',
            'foreign_key' => 'legal_form_id',
            'title_attribute' => 'title',
        ],
    ],
];
