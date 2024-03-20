<?php

use Moox\User\Resources\RoleResource;
use Moox\User\Resources\PermissionResource;

return [
    'navigation_sort' => 701,
    'resources' => [
        'role' => RoleResource::class,
        'permission' => PermissionResource::class,
    ]
];
