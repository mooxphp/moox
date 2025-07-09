<?php

return [
    'packages_path' => env('MOOX_PACKAGES_PATH', 'packages'),
    'branch' => env('MOOX_RELEASE_BRANCH', 'main'),
    'public_repo' => env('MOOX_PUBLIC_REPO', 'moox'),
    'private_repo' => env('MOOX_PRIVATE_REPO', 'pro'),
    'github_org' => env('MOOX_GITHUB_ORG', 'mooxphp'),
];
