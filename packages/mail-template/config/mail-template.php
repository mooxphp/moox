<?php

declare(strict_types=1);

return [
    'readonly' => false,

    'resources' => [
        'mail-template' => [
            'single' => 'trans//mail-template::translations.single',
            'plural' => 'trans//mail-template::translations.plural',
        ],
    ],

    'navigation_group' => 'trans//mail-template::translations.navigation_group',

    /*
    | Blade views that MailTemplate records may link to.
    | Consuming packages should merge additional entries.
    */
    'views' => [],

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
            'display_name' => '{displayName}',
            'last_name' => '{lastName}',
            'name' => '{name}',
        ],
    ],
];
