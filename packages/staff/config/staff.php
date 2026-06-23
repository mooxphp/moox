<?php

use Moox\Staff\Models\Staff;

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
    | - eMail_Account          → email_account
    | - Telefon                → phone
    | - Telefax                → fax
    | - Sprache                → language_code
    | - Änderungsberechtigung  → can_change
    | - Systembenutzer         → is_system_user
    | - is_user_for_services   → is_user_for_services
    | - BCC_bei_Mailversand    → bcc_on_mail_send
    | - ID_Kontakt             → contact_id
    | - SalesUnitGuid          → sales_unit_guid
    | - SalesUnitId            → sales_unit_id
    | - angelegt/geändert/gelöscht audit columns → Laravel timestamps / soft deletes
    |
    | Keys below are stored inside `data` during legacy import:
    */
    'legacy_data_fields' => [
        'bearbeiter_waz',
        'mantis_user',
        'mantis_password',
        'password',
        'email_password',
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

    'relations' => [
    ],

    'taxonomies' => [
    ],

    'navigation_group' => 'Portal',
];
