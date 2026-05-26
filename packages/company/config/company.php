<?php

use Moox\Category\Resources\CategoryResource;
use Moox\Company\Models\Company;
use Moox\Media\Resources\MediaResource;
use Moox\News\Moox\Entities\News\News\NewsResource;
use Moox\Tag\Resources\TagResource;
use Moox\User\Models\User;
use Moox\User\Resources\UserResource;
use Moox\UserDevice\Resources\UserDeviceResource;

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

    'company_types' => [
        'customer',
        'supplier',
        'partner',
        'prospect',
        'internal',
    ],

    'default_currency_code' => 'EUR',

    'resources' => [
        'company' => [

            'single' => 'trans//company::company.company',
            'plural' => 'trans//company::company.companies',

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
                    'label' => 'trans//company::fields.active',
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
                'allowed' => [
                    'news' => [
                        'resource' => NewsResource::class,
                    ],
                    'media' => [
                        'resource' => MediaResource::class,
                    ],
                    'tag' => [
                        'resource' => TagResource::class,
                    ],
                    'category' => [
                        'resource' => CategoryResource::class,
                    ],
                    'user' => [
                        'resource' => UserResource::class,
                    ],
                    'user-device' => [
                        'resource' => UserDeviceResource::class,
                    ],
                ],
                'registry' => [
                    'sources' => [
                        'company' => Company::class,
                    ],
                ],
            ],
        ],
    ],

    'relations' => [
        'parent' => [
            'label' => 'trans//company::fields.parent',
            'relationship' => 'parent',
            'model' => Company::class,
        ],
        'children' => [
            'label' => 'trans//company::fields.children',
            'relationship' => 'children',
            'model' => Company::class,
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
            'label' => 'trans//company::fields.addresses',
            'relationship' => 'addresses',
            'model' => null,
            'pivot_model' => null,
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
