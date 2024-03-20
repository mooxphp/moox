<?php

use Moox\User\Resources\PermissionResource;
use Moox\User\Resources\RoleResource;

return [
    'navigation_sort' => 701,
    'resources' => [
        'role' => RoleResource::class,
        'permission' => PermissionResource::class,
    ],
];
