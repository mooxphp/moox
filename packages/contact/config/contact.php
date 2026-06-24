<?php

use Moox\Company\Models\Company;
use Moox\Company\Resources\CompanyResource;
use Moox\Contact\Models\ContactAssignment;
use Moox\Contact\Models\Contact;

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| Relations are resolved by moox/core (RelationService + ConfigRelationManager).
| Wire pivot models and morph address relations in config/contact.php.
|
*/
return [
    'readonly' => false,

    'statuses' => [
        'draft',
        'active',
        'inactive',
        'approved',
        'archived',
    ],

    'genders' => [
        'unknown',
        'male',
        'female',
        'other',
    ],

    'contact_types' => [
        'external',
        'internal',
    ],

    'resources' => [
        'contact' => [

            'single' => 'trans//contact::contact.contact',
            'plural' => 'trans//contact::contact.contacts',

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'active' => [
                    'label' => 'trans//contact::fields.active',
                    'icon' => 'gmdi-check-circle-o',
                    'query' => [
                        [
                            'field' => 'is_active',
                            'operator' => '=',
                            'value' => true,
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'deleted' => [
                    'label' => 'trans//core::core.deleted',
                    'icon' => 'gmdi-delete',
                    'query' => [
                        [
                            'field' => 'deleted_at',
                            'operator' => '!=',
                            'value' => null,
                        ],
                    ],
                ],
            ],

            'scopes' => [
                'registry' => [
                    'sources' => [
                        'contact' => Contact::class,
                    ],
                ],
            ],
        ],
    ],

    'relations' => [
        'contact_assignments' => [
            'kind' => 'morph_pivot',
            'perspective' => 'related',
            'presentation' => 'tab',
            'label' => 'trans//contact::fields.companies',
            'inverse_label' => 'trans//contact::fields.contacts',
            'relationship' => 'companies',
            'inverse_relationship' => 'contacts',
            'model' => Company::class,
            'related_resource' => CompanyResource::class,
            'pivot_model' => ContactAssignment::class,
            'pivot_table' => 'contact_assignments',
            'morph_name' => 'assignable',
            'related_key' => 'contact_id',
            'pivot_columns' => [
                'role',
                'is_primary',
            ],
            'actions' => [
                'header' => ['attach'],
                'record' => ['edit_pivot', 'detach'],
                'toolbar' => ['detach_bulk'],
            ],
        ],
        'address_assignments' => [
            'kind' => 'morph_pivot',
            'perspective' => 'owner',
            'presentation' => 'tab',
            'label' => 'trans//contact::fields.addresses',
            'translation_prefix' => 'address::fields',
            'relationship' => 'addresses',
            'primary_relationship' => 'address',
            'actions' => [
                'header' => ['attach', 'create'],
                'record' => ['edit_related', 'edit_pivot', 'detach'],
                'toolbar' => ['detach_bulk'],
            ],
        ],
    ],

    'taxonomies' => [
    ],

    'user_models' => [
        App\Models\User::class => [
            'title_attribute' => 'name',
            'label' => 'App User',
        ],
        User::class => [
            'title_attribute' => 'name',
            'label' => 'Moox User',
        ],
    ],

    'navigation_group' => 'Portal',
];
