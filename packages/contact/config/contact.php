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
        'identifiers' => [
            'kind' => 'has_many',
            'presentation' => 'tab',
            'label' => 'trans//contact::contact.contact_identifiers',
            'relationship' => 'identifiers',
            'inverse_relationship' => 'contact',
            'model' => 'Moox\\Contact\\Models\\ContactIdentifier',
            'related_resource' => 'Moox\\Contact\\Resources\\ContactIdentifierResource',
            'foreign_key' => 'contact_id',
            'translation_prefix' => 'contact::fields',
            'display_columns' => ['value'],
            'record_select_search_columns' => ['value'],
            'create_prefill' => [
                'contact_id' => 'owner.id',
            ],
        ],
        'relationships' => [
            'kind' => 'has_many',
            'presentation' => 'tab',
            'label' => 'trans//contact::contact.contact_relationships',
            'relationship' => 'relationships',
            'inverse_relationship' => 'contact',
            'model' => 'Moox\\Contact\\Models\\ContactRelationship',
            'related_resource' => 'Moox\\Contact\\Resources\\ContactRelationshipResource',
            'foreign_key' => 'contact_id',
            'translation_prefix' => 'contact::fields',
            'display_columns' => ['note', 'external_reference', 'related_contact_id'],
            'record_select_search_columns' => ['note', 'external_reference'],
            'create_prefill' => [
                'contact_id' => 'owner.id',
            ],
            'actions' => [
                'header' => ['associate', 'create'],
                'record' => ['view', 'edit'],
            ],
        ],
        'incomingRelationships' => [
            'kind' => 'has_many',
            'presentation' => 'tab',
            'label' => 'trans//contact::contact.incoming_contact_relationships',
            'relationship' => 'incomingRelationships',
            'inverse_relationship' => 'relatedContact',
            'model' => 'Moox\\Contact\\Models\\ContactRelationship',
            'related_resource' => 'Moox\\Contact\\Resources\\ContactRelationshipResource',
            'foreign_key' => 'related_contact_id',
            'translation_prefix' => 'contact::fields',
            'display_columns' => ['note', 'external_reference', 'contact_id'],
            'record_select_search_columns' => ['note', 'external_reference'],
            'create_prefill' => [
                'related_contact_id' => 'owner.id',
            ],
            'actions' => [
                'header' => ['associate', 'create'],
                'record' => ['view', 'edit'],
            ],
        ],
    ],
    'navigationgroup' => [
        'label' => 'CRM',
    ],
];
