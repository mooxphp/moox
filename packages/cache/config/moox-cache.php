<?php

declare(strict_types=1);

return [
    'enabled_targets' => [
        'application-cache',
        'config-cache',
        'route-cache',
        'view-cache',
        'event-cache',
        'compiled',
        'optimize-clear',
    ],

    'cache_keys' => [],

    'history' => [
        'enabled' => true,
        'limit' => 50,
    ],
];
