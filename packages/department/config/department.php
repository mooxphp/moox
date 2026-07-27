<?php

use Moox\Department\Models\Department;
use Moox\Department\Models\DepartmentAssignment;
use Moox\Department\Resources\DepartmentResource;

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| Assignments to companies/contacts are configured on those entities
| (departmentables morph pivot), not on department itself.
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

    'contact_roles' => [
        'member',
        'lead',
        'manager',
    ],

    'company_roles' => [
        'member',
        'lead',
        'manager',
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy overflow → data column
    |--------------------------------------------------------------------------
    |
    | Keys stored inside `data` during legacy import (see comwork-legacy pull).
    | Standard fields live on the departments table.
    */
    'legacy_data_fields' => [
        'legacy_source',
        'legacy_synced_at',
        'legacy_object',
    ],

    'resources' => [
        'department' => [
            'single' => 'trans//department::department.department',
            'plural' => 'trans//department::department.departments',

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
                    'label' => 'trans//department::fields.active',
                    'icon' => 'gmdi-check-circle-o',
                    'query' => [
                        [
                            'field' => 'status',
                            'operator' => '=',
                            'value' => 'active',
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
                        'department' => Department::class,
                    ],
                ],
            ],
        ],
    ],

    'related_morph_defaults' => [
        'display_columns' => ['name', 'code', 'status'],
        'translation_prefix' => 'department::fields',
        'related_resource' => DepartmentResource::class,
        'record_select_search_columns' => ['name', 'code', 'description'],
    ],

    'relations' => [
        'department_assignments' => [
            'kind' => 'pivot_has_many',
            'perspective' => 'related',
            'presentation' => 'tab',
            'label' => 'trans//department::fields.assignments',
            'translation_prefix' => 'department::fields',
            'relationship' => 'departmentAssignments',
            'pivot_model' => DepartmentAssignment::class,
            'pivot_table' => 'department_assignments',
            'morph_name' => 'assignable',
            'pivot_columns' => [
                'is_primary',
                'role',
            ],
            'actions' => [
                'header' => ['create'],
                'record' => ['edit', 'delete'],
            ],
            'owner_types' => [],
        ],
    ],

    'taxonomies' => [
    ],

    'navigation_group' => 'Portal',
];
