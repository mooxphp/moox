<?php

declare(strict_types=1);

use App\Models\User;
use Moox\Audit\Models\Activity;
use Moox\User\Models\User as MooxUser;

return [

    'enabled' => env('AUDIT_ENABLED', true),

    'activity_model' => Activity::class,

    'system_causer' => null,

    'default_entry_type' => 'audit',

    'user_models' => [
        MooxUser::class => [
            'title_attribute' => 'name',
            'label' => 'Moox User',
        ],
        User::class => [
            'title_attribute' => 'name',
            'label' => 'App User',
        ],
    ],

    'presets' => [
        'draft_main' => [
            'entry_type' => 'audit',
            'events' => ['created', 'updated', 'deleted', 'restored'],
        ],
        'draft_translation' => [
            'entry_type' => 'audit',
            'events' => ['created', 'updated', 'deleted', 'restored'],
            'properties' => ['locale'],
            'hidden_attributes' => [
                'created_by_id',
                'created_by_type',
                'updated_by_id',
                'updated_by_type',
                'published_by_id',
                'published_by_type',
                'unpublished_by_id',
                'unpublished_by_type',
                'deleted_by_id',
                'deleted_by_type',
                'restored_by_id',
                'restored_by_type',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | App overrides (optional)
    |--------------------------------------------------------------------------
    |
    | Package defaults register via AuditPackageRegistry. Use these sections to
    | disable models, replace attribute lists, or append fields.
    |
    */

    'models' => [],

    'hooks' => [],

    'filament' => [],

    'retention' => [
        'log' => [
            'live' => 7,
            'archive' => 30,
            'backup' => 365,
        ],
        'audit' => [
            'live' => 30,
            'archive' => 90,
            'backup' => 3650,
        ],
    ],

    'resources' => [
        'audit' => [

            'single' => 'trans//core::audit.audit',
            'plural' => 'trans//core::audit.audits',

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                'log' => [
                    'label' => 'trans//core::audit.entry_type_log',
                    'icon' => 'gmdi-notes',
                    'query' => [
                        [
                            'field' => 'entry_type',
                            'operator' => '=',
                            'value' => 'log',
                        ],
                    ],
                ],
                'audit' => [
                    'label' => 'trans//core::audit.entry_type_audit',
                    'icon' => 'gmdi-fact-check',
                    'query' => [
                        [
                            'field' => 'entry_type',
                            'operator' => '=',
                            'value' => 'audit',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'navigation_group' => 'trans//core::core.system',

];
