<?php

use Moox\Address\Models\Address;
use Moox\Address\Models\Addressable;
use Moox\Company\Models\Company;
use Moox\Company\Resources\CompanyResource;
use Moox\Contact\Models\CompanyContact;
use Moox\Contact\Models\Contact;

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| Greenfield ERP company entity. No payment fields, no employee_id, no
| default-address FKs — addresses and commercial terms use pivots
| (addressables, employee_assignments, commercial_term_assignments).
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
        'companies' => [
            'label' => 'trans//contact::fields.companies',
            'inverse_label' => 'trans//contact::fields.contacts',
            'relationship' => 'companies',
            'inverse_relationship' => 'contacts',
            'model' => Company::class,
            'related_resource' => CompanyResource::class,
            'pivot_model' => CompanyContact::class,
            'pivot_table' => 'company_contact',
            'foreign_key' => 'contact_id',
            'related_key' => 'company_id',
            'pivot_columns' => [
                'role',
                'is_primary',
            ],
        ],
    ],

    /*
    | Morph pivots (moox/core HasMorphPivotRelations + MorphPivotRelationManager).
    | Same keys as address.relations.addressables / draft taxonomies.
    | App sets model, pivot_model, related_resource. Further packages register
    | display_columns via MorphPivotRelationRegistry::registerRelatedModel().
    */
    'morph_relations' => [
        'addressables' => [
            'label' => 'trans//contact::fields.addresses',
            'relationship' => 'addresses',
            'model' => Address::class,
            'pivot_model' => Addressable::class,
            'pivot_table' => 'addressables',
            'morph_name' => 'addressable',
            'pivot_columns' => [
                'billing_address',
                'postal_address',
                'delivery_address',
            ],
            'related_key' => 'address_id',
            'primary' => [
                'on' => 'related',
                'column' => 'id',
                'value' => true,
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
