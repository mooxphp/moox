<?php

use Moox\Contact\Models\Contact;
use Moox\Contact\Resources\ContactResource;
use Moox\Staff\Models\Staff;
use Moox\Staff\Resources\StaffResource;
use Moox\User\Models\User;
use Moox\User\Resources\UserResource;

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| Staff maps legacy Comwork Bearbeiter records to first-class ERP entities.
| Standard employee fields live on the staff table; legacy overflow and
| credentials are stored in the json `data` column (see legacy_data_fields).
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
    | - ID_Kontakt             → contact_id (via contacts.external_reference)
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
        'contact' => [
            'kind' => 'belongs_to',
            'presentation' => 'tab',
            'label' => 'trans//staff::fields.contact',
            'relationship' => 'contact',
            'model' => Contact::class,
            'related_resource' => ContactResource::class,
            'foreign_key' => 'contact_id',
            'display_columns' => ['display_name', 'email', 'status'],
            'badge_columns' => ['status', 'contact_type'],
            'record_select_search_columns' => ['display_name', 'first_name', 'last_name', 'email'],
            'actions' => [
                'header' => ['associate'],
                'record' => ['view', 'edit', 'dissociate'],
            ],
        ],
        'user' => [
            'kind' => 'belongs_to',
            'presentation' => 'tab',
            'label' => 'trans//staff::fields.user',
            'relationship' => 'user',
            'model' => User::class,
            'related_resource' => UserResource::class,
            'foreign_key' => 'user_id',
            'display_columns' => ['name', 'email'],
            'record_select_search_columns' => ['name', 'email'],
            'actions' => [
                'header' => ['associate'],
                'record' => ['view', 'edit', 'dissociate'],
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
