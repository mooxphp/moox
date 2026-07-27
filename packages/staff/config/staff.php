<?php

use Moox\Staff\Models\Staff;
use Moox\Staff\Models\StaffAssignment;
use Moox\Staff\Resources\StaffResource;

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| Staff maps legacy Comwork Bearbeiter records to first-class ERP entities.
| Standard employee fields live on the staff table; legacy overflow and
| credentials are stored in the json `data` column (see legacy_data_fields).
|
| Wire relations to contact, user, company, media, etc. in the published
| application config (config/staff.php), not here.
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

    'company_roles' => [
        'account_manager',
        'deputy_manager',
    ],

    'contact_roles' => [
        'account_manager',
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Bearbeiter → staff / data mapping
    |--------------------------------------------------------------------------
    |
    | Column mapping (Comwork.Bearbeiter):
    | - ID_Bearbeiter          → legacy_id
    | - GUID_Bearbeiter        → external_reference
    | - Bearbeiter             → short_code
    | - Name                   → display_name
    | - eMail_Adresse          → email
    | - Telefon                → phone
    | - ID_Kontakt             → contact_id
    | - angelegt/geändert/gelöscht audit columns → Laravel timestamps / soft deletes
    |
    | Legacy-only Bearbeiter fields are stored inside `data` (see legacy_data_fields):
    | eMail_Account, Telefax, Sprache, Änderungsberechtigung, Systembenutzer,
    | is_user_for_services, BCC_bei_Mailversand, SalesUnitGuid, SalesUnitId
    |
    | Keys below are stored inside `data` during legacy import:
    */
    'legacy_data_fields' => [
        'bearbeiter_waz',
        'mantis_user',
        'mantis_password',
        'password',
        'email_password',
        'email_account',
        'eMail_Account',
        'fax',
        'Telefax',
        'Fax',
        'Sprache',
        'Language',
        'Änderungsberechtigung',
        'Aenderungsberechtigung',
        'Systembenutzer',
        'IsUserAccount',
        'is_user_for_services',
        'IsUserForServices',
        'BCC_bei_Mailversand',
        'BccBeiMailversand',
        'SalesUnitGuid',
        'SalesUnitId',
        'attachment_signature_original',
        'attachment_signature_web',
        'extended_data',
        'legacy_source',
        'legacy_synced_at',
        'legacy_object',
    ],

    'resources' => [
        'staff' => [
            'single' => 'trans//staff::staff.staff_member',
            'plural' => 'trans//staff::staff.staff',

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
                    'label' => 'trans//staff::fields.active',
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
                'internal' => [
                    'label' => 'trans//staff::fields.internal',
                    'icon' => 'gmdi-badge',
                    'query' => [
                        [
                            'field' => 'is_internal',
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
                        'staff' => Staff::class,
                    ],
                ],
            ],
        ],
    ],

    'related_morph_defaults' => [
        'display_columns' => ['display_name', 'short_code', 'email', 'status'],
        'translation_prefix' => 'staff::fields',
        'related_resource' => StaffResource::class,
        'record_select_search_columns' => ['display_name', 'short_code', 'first_name', 'last_name', 'email'],
    ],

    'relations' => [
        'staff_assignments' => [
            'kind' => 'pivot_has_many',
            'perspective' => 'related',
            'presentation' => 'tab',
            'label' => 'trans//staff::fields.assignments',
            'translation_prefix' => 'staff::fields',
            'relationship' => 'staffAssignments',
            'pivot_model' => StaffAssignment::class,
            'pivot_table' => 'staff_assignments',
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
