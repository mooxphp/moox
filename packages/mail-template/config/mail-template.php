<?php

declare(strict_types=1);

return [
    'readonly' => false,

    'resources' => [
        'mail-template' => [
            'single' => 'trans//mail-template::translations.single',
            'plural' => 'trans//mail-template::translations.plural',
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
        ],
    ],

    'navigation_group' => 'trans//mail-template::translations.navigation_group',

    /*
    | Blade layouts that MailTemplate records may link to.
    | Consuming packages should merge additional entries.
    */
    'layouts' => [],

    /*
    | Payload for the Filament HTML preview. Values are shown as `{name}`
    | tokens and highlighted. Consuming apps may merge additional keys.
    */
    'preview_variables' => [
        'invoiceNumber' => '{invoiceNumber}',
        'cta' => '{cta}',
        'magicLink' => '{magicLink}',
        'url' => '{url}',
        'expiresMinutes' => '{expiresMinutes}',
        'headline' => '{headline}',
        'content' => '{content}',
        'displayName' => '{displayName}',
        'user' => [
            'last_name' => '{lastName}',
            'name' => '{name}',
            'display_name' => '{displayName}',
        ],
    ],
];
