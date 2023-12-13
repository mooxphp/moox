<?php

return [
    'resources' => [
        'user' => [
            'enabled' => true,
            'label' => 'User',
            'plural_label' => 'Users',
            'navigation_group' => 'User Group',
            'navigation_icon' => 'heroicon-o-play',
            'navigation_sort' => 1,
            'navigation_count_badge' => true,
            'resource' => Moox\User\Resources\UserResource::class,
        ],
    ],

    /*
     * The resource that will be used for the user management.
     * If you want to use your own resource, you can set this to true.
     * and use `php artisan filament-user:publish` to publish the resource.
     */
    'publish_resource' => true,

    /*
     * The Group name of the resource.
     */
    'group' => 'Settings',

    /*
     * User Filament Impersonate
     */
    'impersonate' => true,

    /*
     * User Filament Shield
     */
    'shield' => true,
];
