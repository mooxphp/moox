<?php

return [
    'packages_path' => env('MOOX_PACKAGES_PATH', base_path('packages')),
    'branch' => env('MOOX_RELEASE_BRANCH', 'main'),
    'public_repo' => env('MOOX_PUBLIC_REPO', 'mooxphp/moox'),
    'private_repo' => env('MOOX_PRIVATE_REPO', 'mooxphp/mooxpro'),
];
