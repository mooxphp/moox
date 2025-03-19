<?php

return [
    'forge_api_key' => env('FORGE_API_KEY'),
    'forge_api_url' => env('FORGE_API_URL', 'https://forge.laravel.com/api/v1'),
    'forge_server_filter' => env('FORGE_SERVER_FILTER', ''), // string to find in server name, optional

    'envoyer_api_key' => env('ENVOYER_API_KEY'),
    'envoyer_api_url' => env('ENVOYER_API_URL', 'https://envoyer.io/api'),
    'envoyer_server_filter' => env('ENVOYER_SERVER_FILTER', ''), // string to find in server name, optional

    'github_token' => env('GITHUB_TOKEN'),
    'github_api_url' => env('GITHUB_API_URL', 'https://api.github.com'),
    'github_org' => env('GITHUB_ORG'),
    'github_repositories' => env('GITHUB_REPOSITORIES', ''), // comma separated list of repositories, optional
];
